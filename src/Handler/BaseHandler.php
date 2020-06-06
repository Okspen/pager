<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Handler;

use Pager\Entity\BotUser;
use Pager\Entity\Dto\CallbackData;
use Pager\Service\Bot\BotSender;
use Pager\Service\Bot\BotUserInterface;
use Pager\Service\Bot\Parts\BotUserAwareInterface;
use Pager\Service\Bot\Parts\CallbackDataAwareInterface;
use Pager\Service\Bot\Screen\RunResult;
use TelegramBot\Api\Types\Update;

abstract class BaseHandler implements HandlerInterface, CallbackDataAwareInterface, BotUserAwareInterface
{
    /** @var CallbackData */
    protected $callbackData;

    /** @var BotSender */
    protected $botSender;

    /** @var BotUser */
    protected $botUser;

    public function setCallbackData(CallbackData $callbackData): void
    {
        $this->callbackData = $callbackData;
    }

    public function setBotSender(BotSender $botSender): void
    {
        $this->botSender = $botSender;
    }

    public function setBotUser(BotUserInterface $botUser): void
    {
        $this->botUser = $botUser;
    }

    abstract public function run(Update $update): RunResult;
}
