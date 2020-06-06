<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City;

use App\Service\Bot\Screen\RunResult;
use App\Service\Bot\Validator\AddressValidator;
use TelegramBot\Api\Types\Update;

class AddressScreen extends BaseCityScreen
{
    public const NAME = 'city.address';

    public function run(): RunResult
    {
        if (false === $this->firstVisit) {
            $address = $this->update->getMessage()->getText();
            $validationResult = AddressValidator::validate($address);

            if (!$validationResult->ok) {
                $this->botSender->sendMessage(
                    $this->botUser,
                    $validationResult->errorMessage
                );

                return RunResult::ok(self::NAME, true);
            }

            $this->botUser->setAddress($address);

            return RunResult::ok(PassportScreen::NAME);
        }

        $this->botSender->sendMessage(
            $this->botUser,
            file_get_contents(__DIR__.'/../../../../Resources/bot_message/contact_address.txt')
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
