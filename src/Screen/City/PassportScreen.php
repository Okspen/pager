<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City;

use App\Service\Bot\Screen\RunResult;
use App\Service\Bot\Validator\PassportValidator;
use TelegramBot\Api\Types\Update;

class PassportScreen extends BaseCityScreen
{
    public const NAME = 'city.passport';

    public function run(): RunResult
    {
        if (false === $this->firstVisit) {
            $passport = $this->update->getMessage()->getText();
            $validationResult = PassportValidator::validate($passport);

            if (false === $validationResult->ok) {
                $this->botSender->sendMessage(
                    $this->botUser,
                    $validationResult->errorMessage
                );

                return RunResult::ok(self::NAME, true);
            }

            $this->botUser->setPassportNumber($passport);

            return RunResult::ok(LicensePhotoScreen::NAME);
        }

        $this->botSender->sendMessage(
            $this->botUser,
            file_get_contents(__DIR__.'/../../../../Resources/bot_message/contact_passport.txt')
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
