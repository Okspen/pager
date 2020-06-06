<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\Admin;

use App\Service\Bot\Screen\AdminBotUserAwareTrait;
use App\Service\Bot\Screen\BaseScreen;
use App\Service\Bot\Screen\RunResult;
use App\Service\Bot\Validator\PhoneValidator;
use TelegramBot\Api\Types\Contact;
use TelegramBot\Api\Types\ReplyKeyboardHide;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;

class ShareContactScreen extends BaseScreen
{
    use AdminBotUserAwareTrait;

    public const NAME = 'admin.share.contact';

    public function run(): RunResult
    {
        if (null !== $this->botUser->getPhone()) {
            return RunResult::ok(StartScreen::NAME, true);
        }

        if (!$this->firstVisit) {
            $message = $this->update->getMessage();

            /** @var Contact|null $contact */
            $contact = $message->getContact();
            if (null === $contact) {
                return RunResult::ok(self::NAME, true);
            }

            $phone = $contact->getPhoneNumber();
            $validationResult = PhoneValidator::validate($phone);

            if (!$validationResult->ok) {
                $this->botSender->sendMessage(
                    $this->botUser,
                    $validationResult->errorMessage
                );

                return RunResult::ok(self::NAME, true);
            }

            $this->botUser->setFirstName($contact->getFirstName());
            $this->botUser->setLastName($contact->getLastName());
            $this->botUser->setPhone($contact->getPhoneNumber());

            $parameters = $this->botUser->getParameters();
            $parameters->back();
            $this->botUser->setParameters($parameters);

            $this->botSender->sendMessage(
                $this->botUser,
                'Спасибо!',
                new ReplyKeyboardHide()
            );

            return RunResult::ok(StartScreen::NAME, true);
        }

        $keyboardArray = [
            [
                ['text' => 'Отправить контакт', 'request_contact' => true],
            ],
        ];

        $this->botSender->sendMessage(
            $this->botUser,
            'Для работы с ботом, пришлите, пожалуйста, ваш контакт',
            new ReplyKeyboardMarkup($keyboardArray, true, true)
        );

        return RunResult::ok();
    }
}
