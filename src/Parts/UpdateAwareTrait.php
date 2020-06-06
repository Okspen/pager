<?php

declare(strict_types=1);

namespace Pager\Parts;

use TelegramBot\Api\Types\Update;

trait UpdateAwareTrait
{
    /** @var Update */
    protected $update;

    public function setUpdate(Update $update): void
    {
        $this->update = $update;
    }
}
