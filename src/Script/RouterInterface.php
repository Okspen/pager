<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Script;

use Pager\Service\Bot\BotUserInterface;
use Pager\Service\Bot\Screen\ScreenInterface;
use TelegramBot\Api\Types\Update;

interface RouterInterface
{
    /**
     * Decides which screen should be loaded and returns its name.
     */
    public function routeAndReturnName(Update $update, BotUserInterface $botUser): string;

    /**
     * Initializes instance of the screen.
     */
    public function instantiatePreparedScreen(BotUserInterface $botUser, string $screenName, ?Update $update = null): ?ScreenInterface;
}
