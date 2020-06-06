<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Parts;

use TelegramBot\Api\Types\Update;

interface UpdateAwareInterface
{
    public function setUpdate(Update $update): void;
}
