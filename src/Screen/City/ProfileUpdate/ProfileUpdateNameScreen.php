<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City\ProfileUpdate;

use Pager\Service\Bot\Screen\City\BaseCityScreen;
use Pager\Service\Bot\Screen\RunResult;
use Pager\Service\Bot\Validator\NameValidator;
use TelegramBot\Api\Types\Update;

class ProfileUpdateNameScreen extends BaseCityScreen
{
    public const NAME = 'city.profile.update.name';

    public function run(): RunResult
    {
        if (!$this->firstVisit) {
            $name = $this->update->getMessage()->getText();
            $validationResult = NameValidator::validate($name);

            if (!$validationResult->ok) {
                $this->botSender->sendMessage(
                    $this->botUser,
                    $validationResult->errorMessage
                );

                return RunResult::ok(self::NAME, true);
            }

            $this->botUser->setOverrideName($name);

            $parameters = $this->botUser->getParameters();

            $fieldComments = $parameters->getFieldComments();
            $updatedFields = $parameters->getUpdatedFields();

            $fieldComments->overrideName = null;
            $updatedFields->overrideName = true;

            $parameters->setFieldComments($fieldComments);
            $parameters->setUpdatedFields($updatedFields);

            $this->botUser->setParameters($parameters);

            $this->botSender->sendMessage($this->botUser, 'Спасибо!');

            return RunResult::ok(ProfileUpdateScreen::NAME, true);
        }

        $message = 'Отправьте новое имя сообщением!';
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
