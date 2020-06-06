<?php

declare(strict_types=1);

namespace Pager\Service\Bot;

use App\Entity\AdminBotUser;
use App\Entity\Dto\BotUserParameters;
use App\Entity\Dto\CallbackData;
use App\Event\BotUserCreatedEvent;
use App\Repository\AdminBotUserRepository;
use App\Service\Bot\Handler\HandlerInterface;
use App\Service\Bot\Parts\BotUserAwareInterface;
use App\Service\Bot\Parts\CallbackDataAwareInterface;
use App\Service\Bot\Screen\RunResult;
use App\Service\Bot\Script\AdminScreenRouter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\CallbackQuery;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;
use TelegramBot\Api\Types\User;

class AdminBot implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var Client */
    private $client;

    /** @var AdminBotUserRepository */
    private $adminBotUserRepository;

    /** @var AdminScreenRouter */
    private $screenRouter;

    /** @var AdminBotSender */
    private $botSender;

    /** @var ServiceRegistryInterface */
    private $handlerRegistry;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var LoggerInterface */
    private $incomingLogger;

    public function __construct(
        AdminBotUserRepository $botUserRepository,
        AdminScreenRouter $screenRouter,
        AdminBotSender $botSender,
        ServiceRegistryInterface $handlerRegistry,
        LoggerInterface $incomingLogger,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->adminBotUserRepository = $botUserRepository;
        $this->screenRouter = $screenRouter;
        $this->botSender = $botSender;
        $this->handlerRegistry = $handlerRegistry;
        $this->incomingLogger = $incomingLogger;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function run(Request $request): void
    {
        $this->botSender->init();
        $this->client = new Client($this->botSender->getToken());

        $this->incomingLogger->info(
            json_encode(
                json_decode(file_get_contents('php://input')),
                JSON_UNESCAPED_UNICODE
            )
        );

        $this->client->on(function (Update $update): void {
            $botUser = $this->initUser($update->getMessage()->getFrom());

            $screen = $this->screenRouter->routeAndReturnName($update, $botUser);

            try {
                $result = RunResult::ok($screen);
                while (null !== $result->next) {
                    $screen = $this->screenRouter->instantiatePreparedScreen($botUser, $result->next, $update);
                    $screen->setBotSender($this->botSender);

                    if (null !== $result->firstVisit) {
                        $screen->setFirstVisit($result->firstVisit);
                    }

                    $result = $screen->run();

                    $this->adminBotUserRepository->save($botUser);
                }
            } catch (\Throwable $e) {
                $this->handleException($e, $botUser);
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
                $handler = $this->handlerRegistry->get('admin.'.str_replace('-', '.', $callbackData->commandName));
                $handler->setBotSender($this->botSender);

                if ($handler instanceof BotUserAwareInterface) {
                    $handler->setBotUser($botUser);
                }

                if ($handler instanceof CallbackDataAwareInterface) {
                    $handler->setCallbackData($callbackData);
                }

                $handler->run($update);
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

    private function initUser(User $user): AdminBotUser
    {
        /** @var AdminBotUser $botUser */
        $botUser = $this->adminBotUserRepository->findOneByUserId($user->getId());

        if (null === $botUser) {
            /** @var AdminBotUser $botUser */
            $deletedBotUser = $this->adminBotUserRepository->findDeletedByUserId($user->getId());
            if (null !== $deletedBotUser) {
                $deletedBotUser->setDeletedAt(null);
                $deletedBotUser->setParameters(new BotUserParameters());
                $botUser = $deletedBotUser;
            } else {
                $botUser = new AdminBotUser();
            }

            $botUser->setUserId((string) $user->getId());
            $botUser->setUserName($user->getUsername());
            $botUser->setFirstName($user->getFirstName());

            $this->adminBotUserRepository->save($botUser);

            $this->eventDispatcher->dispatch(new BotUserCreatedEvent($botUser));
        }

        return $botUser;
    }

    private function handleException(\Throwable $e, BotUserInterface $botUser): void
    {
        $this->botSender->sendMessage($botUser, 'Произошла ошибка, попробуйте еще раз');
        $this->logger->error($e->getMessage(), ['exception' => $e]);
    }
}
