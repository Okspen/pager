<?php

declare(strict_types=1);

namespace Pager\Service\Bot;

use App\Entity\BotUser;
use App\Entity\Dto\BotUserParameters;
use App\Entity\Dto\CallbackData;
use App\Entity\Enum\BotUserStatus;
use App\Event\BotUserCreatedEvent;
use App\Repository\BotUserRepository;
use App\Service\Bot\Handler\HandlerInterface;
use App\Service\Bot\Parts\BotUserAwareInterface;
use App\Service\Bot\Parts\CallbackDataAwareInterface;
use App\Service\Bot\Processor\ScreenProcessor;
use App\Service\Bot\Script\CityScreenRouter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\CallbackQuery;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;
use TelegramBot\Api\Types\User;

class CityBot implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const LOCK_TTL = 60;

    /** @var Client */
    private $client;

    /** @var BotUserRepository */
    private $botUserRepository;

    /** @var CityScreenRouter */
    private $screenRouter;

    /** @var ScreenProcessor */
    private $screenProcessor;

    /** @var ServiceRegistryInterface */
    private $handlerRegistry;

    /** @var BotSender */
    private $botSender;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var LoggerInterface */
    private $incomingLogger;

    /** @var LockFactory */
    private $lockFactory;

    /** @var CityContext */
    private $cityContext;

    public function __construct(
        BotUserRepository $botUserRepository,
        CityScreenRouter $screenRouter,
        CityContext $cityContext,
        ScreenProcessor $screenProcessor,
        ServiceRegistryInterface $handlerRegistry,
        BotSender $botSender,
        LoggerInterface $incomingLogger,
        LockFactory $lockFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->botUserRepository = $botUserRepository;
        $this->cityContext = $cityContext;
        $this->screenRouter = $screenRouter;
        $this->screenProcessor = $screenProcessor;
        $this->handlerRegistry = $handlerRegistry;
        $this->botSender = $botSender;
        $this->incomingLogger = $incomingLogger;
        $this->lockFactory = $lockFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function run(Request $request): void
    {
        $this->incomingLogger->info(
            json_encode(
                json_decode(file_get_contents('php://input')),
                JSON_UNESCAPED_UNICODE
            )
        );

        $this->client = new Client($this->cityContext->getCity()->getBotToken());

        $this->client->on(function (Update $update): void {
            $botUser = $this->initUser($update->getMessage()->getFrom());

            $lock = $this->generateLockForBotUser($botUser);

            try {
                if (!$lock->acquire()) {
                    return;
                }

                $screen = $this->screenRouter->routeAndReturnName($update, $botUser);
                $this->botUserRepository->save($botUser);

                $this->screenProcessor->setBotSender($this->botSender);
                $this->screenProcessor->setUpdate($update);
                $this->screenProcessor->setBotUser($botUser);

                $this->screenProcessor->process($screen);
            } catch (LockAcquiringException | LockConflictedException $e) {
                return;
            } finally {
                if ($lock->isAcquired()) {
                    $lock->release();
                }
            }
        }, function (Update $update): bool {
            /** @var Message|null $message */
            $message = $update->getMessage();

            return null !== $message;
        });

        $this->client->on(function (Update $update): void {
            $botUser = $this->initUser($update->getCallbackQuery()->getFrom());

            $callbackData = new CallbackData($update->getCallbackQuery()->getId(), $update->getCallbackQuery()->getData());

            try {
                /** @var HandlerInterface $handler */
                $handler = $this->handlerRegistry->get(str_replace('-', '.', $callbackData->commandName));
                $handler->setBotSender($this->botSender);

                if ($handler instanceof BotUserAwareInterface) {
                    $handler->setBotUser($botUser);
                }

                if ($handler instanceof CallbackDataAwareInterface) {
                    $handler->setCallbackData($callbackData);
                }

                $result = $handler->run($update);
                if (null !== $result->next) {
                    $this->screenProcessor->setBotUser($botUser);
                    $this->screenProcessor->setBotSender($this->botSender);
                    $this->screenProcessor->setUpdate($update);
                    $this->screenProcessor->process($result->next);
                }
            } catch (\Throwable $e) {
                $this->handleException($e, $botUser);
            }
        }, function (Update $update): bool {
            /** @var CallbackQuery|null $callbackQuery */
            $callbackQuery = $update->getCallbackQuery();

            return null !== $callbackQuery;
        });

        try {
            $this->client->run();
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['e' => $e]);
        }
    }

    private function initUser(User $user): BotUser
    {
        $botUser = $this->botUserRepository->findOneByUserId($user->getId());

        if (null === $botUser) {
            $deletedBotUser = $this->botUserRepository->findDeletedByUserId($user->getId());
            // if bot user was deleted
            if (null !== $deletedBotUser) {
                $deletedBotUser->setDeletedAt(null);
                $deletedBotUser->setStatus(BotUserStatus::UNCOMPLETED());
                $deletedBotUser->setParameters(new BotUserParameters());
                $botUser = $deletedBotUser;
            } else {
                $botUser = new BotUser();
            }

            $botUser->setCity($this->cityContext->getCity());
            $botUser->setUserId((string) $user->getId());
            $botUser->setUserName($user->getUsername());
            $botUser->setFirstName($user->getFirstName());

            $this->botUserRepository->save($botUser);

            $this->eventDispatcher->dispatch(new BotUserCreatedEvent($botUser));
        }

        $botUser->updateLiveAt();

        return $botUser;
    }

    private function handleException(\Throwable $e, BotUserInterface $botUser): void
    {
        $this->botSender->sendMessage($botUser, 'Произошла ошибка, попробуйте еще раз');
        $this->logger->error($e->getMessage(), ['exception' => $e]);
    }

    private function generateLockForBotUser($botUser): LockInterface
    {
        return $this->lockFactory->createLock(
            sprintf('botuser.%s', $botUser->getId()),
            self::LOCK_TTL
        );
    }
}
