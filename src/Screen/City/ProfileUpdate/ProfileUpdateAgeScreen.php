<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City\ProfileUpdate;

use Pager\Service\Bot\Screen\City\BaseCityScreen;
use Pager\Service\Bot\Screen\RunResult;
use Pager\Service\Bot\Validator\AgeValidator;
use TelegramBot\Api\Types\Update;

class ProfileUpdateAgeScreen extends BaseCityScreen
{
    public const NAME = 'city.profile.update.age';

    public function run(): RunResult
    {
        if (!$this->firstVisit) {
            $age = $this->update->getMessage()->getText();
            $validationResult = AgeValidator::validate($age);

            if (!$validationResult->ok) {
                $this->botSender->sendMessage(
                    $this->botUser,
                    $validationResult->errorMessage
                );

                return RunResult::ok(self::NAME, true);
            }

            $this->botUser->setAge((int) $age);

            $parameters = $this->botUser->getParameters();

            $fieldComments = $parameters->getFieldComments();
            $updatedFields = $parameters->getUpdatedFields();

            $fieldComments->age = null;
            $updatedFields->age = true;

            $parameters->setFieldComments($fieldComments);
            $parameters->setUpdatedFields($updatedFields);

            $this->botUser->setParameters($parameters);

            $this->botSender->sendMessage($this->botUser, 'Спасибо!');

            return RunResult::ok(ProfileUpdateScreen::NAME, true);
        }

        $message = 'Отправьте Ваш возраст сообщением!';
        $this->botSender->sendMessage($this->botUser, $message);

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
