<?php

declare(strict_types=1);

namespace Pager\Handler;

use Pager\Screen\RunResult;
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
