<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City;

use App\Service\Bot\Screen\RunResult;
use App\Service\Bot\Validator\PhoneValidator;
use TelegramBot\Api\Types\Update;

class FriendsPhoneScreen extends BaseCityScreen
{
    public const NAME = 'city.friends.phone';

    public function run(): RunResult
    {
        if (false === $this->firstVisit) {
            $phone = $this->update->getMessage()->getText();
            $validationResult = PhoneValidator::validate($phone);

            if (!$validationResult->ok) {
                $this->botSender->sendMessage(
                    $this->botUser,
                    $validationResult->errorMessage
                );

                return RunResult::ok(self::NAME, true);
            }

            $this->botUser->setFriendsPhone($phone);

            return RunResult::ok(ReadyToHaveMoneyScreen::NAME, true);
        }

        $this->botSender->sendMessage(
            $this->botUser,
            file_get_contents(__DIR__.'/../../../../Resources/bot_message/contact_friends_phone.txt')
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
