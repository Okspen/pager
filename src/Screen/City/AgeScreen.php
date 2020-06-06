<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City;

use Pager\Service\Bot\Screen\RunResult;
use Pager\Service\Bot\Validator\AgeValidator;
use TelegramBot\Api\Types\Update;

class AgeScreen extends BaseCityScreen
{
    public const NAME = 'city.age';

    public function run(): RunResult
    {
        if (false === $this->firstVisit) {
            $age = $this->update->getMessage()->getText();
            $validationResult = AgeValidator::validate($age);
            if (false === $validationResult->ok) {
                $this->botSender->sendMessage(
                    $this->botUser,
                    $validationResult->errorMessage
                );

                return RunResult::ok(self::NAME, true);
            }

            $this->botUser->setAge((int) $age);

            return RunResult::ok(LicensePhotoScreen::NAME);
            //return RunResult::ok(AddressScreen::NAME); // TODO: UNCOMMENT
        }

        $this->botSender->sendMessage(
            $this->botUser,
            file_get_contents(__DIR__.'/../../../../Resources/bot_message/contact_age.txt')
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
