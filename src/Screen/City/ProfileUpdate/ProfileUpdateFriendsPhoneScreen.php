<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City\ProfileUpdate;

use Pager\Service\Bot\Screen\City\BaseCityScreen;
use Pager\Service\Bot\Screen\RunResult;
use Pager\Service\Bot\Validator\PhoneValidator;
use TelegramBot\Api\Types\Update;

class ProfileUpdateFriendsPhoneScreen extends BaseCityScreen
{
    public const NAME = 'city.profile.update.friends.phone';

    public function run(): RunResult
    {
        if (!$this->firstVisit) {
            $phone = $this->update->getMessage()->getText();
            $validationResult = PhoneValidator::validate($phone);

            if (false === $validationResult->ok) {
                $this->botSender->sendMessage(
                    $this->botUser,
                    $validationResult->errorMessage
                );

                return RunResult::ok();
            }

            $this->botUser->setFriendsPhone($phone);

            $parameters = $this->botUser->getParameters();

            $fieldComments = $parameters->getFieldComments();
            $updatedFields = $parameters->getUpdatedFields();

            $fieldComments->friendsPhone = null;
            $updatedFields->friendsPhone = true;

            $parameters->setFieldComments($fieldComments);
            $parameters->setUpdatedFields($updatedFields);

            $this->botUser->setParameters($parameters);

            $this->botSender->sendMessage($this->botUser, 'Спасибо!');

            return RunResult::ok(ProfileUpdateScreen::NAME, true);
        }

        $message = 'Отправьте новый телефон знакомого или друга сообщением!';
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
