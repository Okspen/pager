<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Parts;

use App\Service\Bot\BotUserInterface;

trait BotUserAwareTrait
{
    /** @var BotUserInterface */
    protected $botUser;

    public function setBotUser(BotUserInterface $botUser): void
    {
        $this->botUser = $botUser;
    }
}
