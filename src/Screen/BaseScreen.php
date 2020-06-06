<?php

declare(strict_types=1);

namespace Pager\Screen;

use Pager\BotSenderInterface;
use Pager\Parts\BotUserAwareInterface;
use Pager\Parts\UpdateAwareInterface;
use Pager\Parts\UpdateAwareTrait;
use TelegramBot\Api\Types\Update;

abstract class BaseScreen implements ScreenInterface, BotUserAwareInterface, UpdateAwareInterface
{
    use UpdateAwareTrait;

    public const NAME = 'base';

    /** @var BotSenderInterface */
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
        return self::NAME;
    }

    public function setBotSender(BotSenderInterface $botSender): void
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
