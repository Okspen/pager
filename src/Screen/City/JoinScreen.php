<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City;

use Pager\Entity\Enum\BotUserStatus;
use Pager\Event\BotUserSharedContactEvent;
use Pager\Normalizer\PhoneNormalizer;
use Pager\Service\Bot\Screen\RunResult;
use Pager\Service\Bot\Validator\PhoneValidator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use TelegramBot\Api\Types\Contact;
use TelegramBot\Api\Types\ReplyKeyboardHide;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class JoinScreen extends BaseCityScreen
{
    public const NAME = 'city.join';

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function addRoute(?Update $update = null): ?string
    {
        return self::NAME;
    }

    public function run(): RunResult
    {
        if ($this->botUser->getStatus()->equals(BotUserStatus::MODERATED())) {
            return RunResult::ok(FinalScreen::NAME);
        }

        if ($this->botUser->getStatus()->equals(BotUserStatus::NEW())) {
            return RunResult::ok(FinalScreen::NAME);
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
            $this->botUser->setPhone(PhoneNormalizer::normalize($contact->getPhoneNumber()));

            $parameters = $this->botUser->getParameters();
            $parameters->back();
            $this->botUser->setParameters($parameters);

            $this->botSender->sendMessage(
                $this->botUser,
                file_get_contents(__DIR__.'/../../../../Resources/bot_message/contact_received.txt'),
                new ReplyKeyboardHide()
            );

            $this->eventDispatcher->dispatch(new BotUserSharedContactEvent($this->botUser));

            return RunResult::ok(NameScreen::NAME);
        }

        $keyboardArray = [
            [
                ['text' => 'Отправить контакт', 'request_contact' => true],
            ],
        ];

        $this->botSender->sendMessage(
            $this->botUser,
            file_get_contents(__DIR__.'/../../../../Resources/bot_message/contact_start.txt'),
            new ReplyKeyboardMarkup($keyboardArray, true, true)
        );

        return RunResult::ok();
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
