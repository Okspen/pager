<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Parts;

use App\Service\Bot\BotUserInterface;

interface BotUserAwareInterface
{
    public function setBotUser(BotUserInterface $botUser): void;
}
