<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City\ProfileUpdate;

use Pager\Service\Bot\Screen\City\BaseCityScreen;
use Pager\Service\Bot\Screen\RunResult;
use Pager\Service\Bot\Validator\AddressValidator;
use TelegramBot\Api\Types\Update;

class ProfileUpdateAddressScreen extends BaseCityScreen
{
    public const NAME = 'city.profile.update.address';

    public function run(): RunResult
    {
        if (!$this->firstVisit) {
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

            $parameters = $this->botUser->getParameters();

            $fieldComments = $parameters->getFieldComments();
            $updatedFields = $parameters->getUpdatedFields();

            $fieldComments->address = null;
            $updatedFields->address = true;

            $parameters->setFieldComments($fieldComments);
            $parameters->setUpdatedFields($updatedFields);

            $this->botUser->setParameters($parameters);

            $this->botSender->sendMessage($this->botUser, 'Спасибо!');

            return RunResult::ok(ProfileUpdateScreen::NAME, true);
        }

        $message = 'Отправьте новый адрес сообщением!';
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
