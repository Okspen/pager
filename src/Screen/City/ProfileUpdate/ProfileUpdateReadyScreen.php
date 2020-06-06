<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City\ProfileUpdate;

use App\Service\Bot\Screen\City\BaseCityScreen;
use App\Service\Bot\Screen\RunResult;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class ProfileUpdateReadyScreen extends BaseCityScreen
{
    public const NAME = 'city.profile.update.ready';

    public function run(): RunResult
    {
        if (false === $this->firstVisit) {
            $text = $this->update->getMessage()->getText();

            if (!\in_array($text, ['Да', 'Нет'], true)) {
                return RunResult::ok(self::NAME, true);
            }

            $this->botUser->setReadyForMoney('Да' === $text);

            $parameters = $this->botUser->getParameters();

            $fieldComments = $parameters->getFieldComments();
            $updatedFields = $parameters->getUpdatedFields();

            $fieldComments->readyForMoney = null;
            $updatedFields->readyForMoney = true;

            $parameters->setFieldComments($fieldComments);
            $parameters->setUpdatedFields($updatedFields);

            $this->botUser->setParameters($parameters);

            $this->botSender->sendMessage($this->botUser, 'Спасибо!');

            return RunResult::ok(ProfileUpdateScreen::NAME, true);
        }

        $keyboard = new ReplyKeyboardMarkup([['Да'], ['Нет']], true, true);

        $this->botSender->sendMessage(
            $this->botUser,
            file_get_contents(__DIR__.'/../../../../../Resources/bot_message/contact_ready_for_money.txt'),
            $keyboard
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
