<?php

declare(strict_types=1);

namespace Pager\Parts;

use Pager\BotUserInterface;

interface BotUserAwareInterface
{
    public function setBotUser(BotUserInterface $botUser): void;
}
