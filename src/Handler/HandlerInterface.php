<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Handler;

use Pager\Service\Bot\Screen\RunResult;
use TelegramBot\Api\Types\Update;

/**
 * Interface HandlerInterface.
 *
 * Callback data handler interface
 */
interface HandlerInterface
{
    public function run(Update $update): RunResult;
}
