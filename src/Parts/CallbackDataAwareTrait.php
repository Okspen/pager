<?php

declare(strict_types=1);

namespace Pager\Parts;

use App\Entity\Dto\CallbackData;

trait CallbackDataAwareTrait
{
    /** @var CallbackData */
    protected $callbackData;

    public function setCallbackData(CallbackData $callbackData): void
    {
        $this->callbackData = $callbackData;
    }
}
