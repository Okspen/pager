<?php

declare(strict_types=1);

namespace Pager\Screen;

use Pager\BotSenderInterface;
use TelegramBot\Api\Types\Update;

interface ScreenInterface
{
    /**
     * Use it to prepare bot user's path for screen execution.
     */
    public function addRoute(?Update $update = null): ?string;

    public function run(): RunResult;

    public function setUpdate(Update $update): void;

    public function setBotSender(BotSenderInterface $botApi): void;

    public function setFirstVisit(bool $firstVisit): void;

    public function getName(): string;
}
