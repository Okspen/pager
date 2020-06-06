<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen;

use Pager\Entity\AdminBotUser;
use Pager\Service\Bot\BotUserInterface;

trait AdminBotUserAwareTrait
{
    /** @var AdminBotUser */
    protected $botUser;

    public function setBotUser(BotUserInterface $botUser): void
    {
        if (!$botUser instanceof AdminBotUser) {
            throw new \InvalidArgumentException();
        }

        $this->botUser = $botUser;
    }
}
