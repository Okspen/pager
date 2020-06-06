<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City;

use App\Service\Bot\Screen\RunResult;
use TelegramBot\Api\Types\ReplyKeyboardHide;
use TelegramBot\Api\Types\Update;

class FinalScreen extends BaseCityScreen
{
    public const NAME = 'city.final';

    public function run(): RunResult
    {
        $this->botSender->sendMessage(
            $this->botUser,
            file_get_contents(__DIR__.'/../../../../Resources/bot_message/contact_final.txt'),
            new ReplyKeyboardHide()
        );

        return RunResult::ok();
    }

    /**
     * This function is supposed to return the route for update.
     * The following bot workflow should be based on this specific route.
     */
    public function addRoute(?Update $update = null): ?string
    {
        return self::NAME;
    }
}
