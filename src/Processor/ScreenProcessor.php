<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Processor;

use App\Repository\BotUserRepositoryInterface;
use App\Service\Bot\BotSender;
use App\Service\Bot\BotUserInterface;
use App\Service\Bot\Screen\RunResult;
use App\Service\Bot\Script\RouterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TelegramBot\Api\Types\Update;

class ScreenProcessor implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var RouterInterface */
    private $router;

    /** @var BotUserInterface|null */
    private $botUser;

    /** @var Update|null */
    private $update;

    /** @var BotSender|null */
    private $botSender;

    /** @var BotUserRepositoryInterface */
    private $botUserRepository;

    public function __construct(RouterInterface $router, BotUserRepositoryInterface $botUserRepository)
    {
        $this->router = $router;
        $this->botUserRepository = $botUserRepository;
    }

    public function setBotUser(BotUserInterface $botUser): void
    {
        $this->botUser = $botUser;
    }

    public function setUpdate(Update $update): void
    {
        $this->update = $update;
    }

    public function setBotSender(BotSender $botSender): void
    {
        $this->botSender = $botSender;
    }

    public function process(string $screenName): void
    {
        try {
            $result = RunResult::ok($screenName);
            while (null !== $result->next) {
                $screen = $this->router->instantiatePreparedScreen($this->botUser, $result->next, $this->update);
                $screen->setBotSender($this->botSender);

                if (null !== $result->firstVisit) {
                    $screen->setFirstVisit($result->firstVisit);
                }

                $result = $screen->run();

                $this->botUserRepository->save($this->botUser);
            }
        } catch (\Throwable $e) {
            $this->handleException($e, $this->botUser);
        }
    }

    private function handleException(\Throwable $e, BotUserInterface $botUser): void
    {
        $this->botSender->sendMessage($botUser, 'Произошла ошибка, попробуйте еще раз');
        $this->logger->error($e->getMessage(), ['exception' => $e]);
    }
}
