<?php

declare(strict_types=1);

namespace Pager\Handler;

use Pager\Dto\CallbackData;
use Pager\Entity\BotUser;
use Pager\BotSenderInterface;
use Pager\BotUserInterface;
use Pager\Parts\BotUserAwareInterface;
use Pager\Parts\CallbackDataAwareInterface;
use Pager\Screen\RunResult;
use TelegramBot\Api\Types\Update;

abstract class BaseHandler implements HandlerInterface, CallbackDataAwareInterface, BotUserAwareInterface
{
    /** @var CallbackData */
    protected $callbackData;

    /** @var BotSenderInterface */
    protected $botSender;

    /** @var BotUser */
    protected $botUser;

    public function setCallbackData(CallbackData $callbackData): void
    {
        $this->callbackData = $callbackData;
    }

    public function setBotSender(BotSenderInterface $botSender): void
    {
        $this->botSender = $botSender;
    }

    public function setBotUser(BotUserInterface $botUser): void
    {
        $this->botUser = $botUser;
    }

    abstract public function run(Update $update): RunResult;
}
