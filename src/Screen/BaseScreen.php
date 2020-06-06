<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen;

use Pager\Service\Bot\BotSender;
use Pager\Service\Bot\Parts\BotUserAwareInterface;
use Pager\Service\Bot\Parts\UpdateAwareInterface;
use Pager\Service\Bot\Parts\UpdateAwareTrait;
use TelegramBot\Api\Types\Update;

abstract class BaseScreen implements ScreenInterface, BotUserAwareInterface, UpdateAwareInterface
{
    use UpdateAwareTrait;

    public const NAME = 'base';

    /** @var BotSender */
    protected $botSender;

    /** @var bool */
    protected $firstVisit = false;

    abstract public function run(): RunResult;

    /**
     * This method is supposed to return the route for update.
     * The following bot workflow should be based on this specific route.
     */
    public function addRoute(?Update $update = null): ?string
    {
        return static::NAME;
    }

    public function setBotSender(BotSender $botSender): void
    {
        $this->botSender = $botSender;
    }

    public function setFirstVisit(bool $firstVisit): void
    {
        $this->firstVisit = $firstVisit;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
