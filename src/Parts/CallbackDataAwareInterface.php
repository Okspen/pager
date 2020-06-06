<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Parts;

use Pager\Entity\Dto\CallbackData;

interface CallbackDataAwareInterface
{
    public function setCallbackData(CallbackData $callbackData): void;
}
