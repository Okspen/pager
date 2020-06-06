<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City;

use App\Entity\BotUser;
use App\Service\Bot\BotUserInterface;
use App\Service\Bot\Screen\BaseScreen;

abstract class BaseCityScreen extends BaseScreen
{
    /** @var BotUser */
    protected $botUser;

    public function setBotUser(BotUserInterface $botUser): void
    {
        if (!$botUser instanceof BotUser) {
            throw new \InvalidArgumentException();
        }

        $this->botUser = $botUser;
    }
}
