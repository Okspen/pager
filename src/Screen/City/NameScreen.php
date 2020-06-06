<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City;

use Pager\Service\Bot\Screen\RunResult;
use Pager\Service\Bot\Validator\NameValidator;
use TelegramBot\Api\Types\Update;

class NameScreen extends BaseCityScreen
{
    public const NAME = 'city.name';

    public function run(): RunResult
    {
        if (false === $this->firstVisit) {
            $name = $this->update->getMessage()->getText();
            $validationResult = NameValidator::validate($name);
            if (false === $validationResult->ok) {
                $this->botSender->sendMessage(
                    $this->botUser,
                    $validationResult->errorMessage
                );

                return RunResult::ok(self::NAME, true);
            }

            $this->botUser->setOverrideName($name);

            return RunResult::ok(AgeScreen::NAME);
        }

        $this->botSender->sendMessage(
            $this->botUser,
            file_get_contents(__DIR__.'/../../../../Resources/bot_message/contact_name.txt')
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
