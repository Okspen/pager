<?php

declare(strict_types=1);

namespace Pager\Parts;

use Pager\Dto\CallbackData;

interface CallbackDataAwareInterface
{
    public function setCallbackData(CallbackData $callbackData): void;
}
