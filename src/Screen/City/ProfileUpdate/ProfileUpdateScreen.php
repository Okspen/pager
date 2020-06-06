<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City\ProfileUpdate;

use Pager\Event\BotUserUpdatedProfileEvent;
use Pager\Service\Bot\Screen\City\BaseCityScreen;
use Pager\Service\Bot\Screen\RunResult;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use TelegramBot\Api\Types\ReplyKeyboardHide;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class ProfileUpdateScreen extends BaseCityScreen implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const NAME = 'city.profile.update';

    private const READY_BUTTON = 'Готово';

    /** @var array */
    private static $map = [
        'Имя' => 'override_name',
        'Адрес' => 'address',
        'Возраст' => 'age',
        'Телефон друга' => 'friends_phone',
        'Номер паспорта' => 'passport_number',
        'Фото' => 'profile_photo_path',
        'Фото документа' => 'license_photo_path',
        'Видео' => 'profile_video_path',
        'Готовы иметь при себе 2000 рублей?' => 'ready_for_money',
    ];

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function run(): RunResult
    {
        if (!$this->firstVisit) {
            $text = $this->update->getMessage()->getText();
            $reallyReady = 0 === \count($this->getUpdateRequiredFieldKeys());

            if ($reallyReady || self::READY_BUTTON === $text) {
                if (!$reallyReady) {
                    $keyboard = $this->buildKeyboard();
                    $this->botSender->sendMessage($this->botUser, 'Еще не все поля изменены', $keyboard);

                    return RunResult::ok();
                }

                $this->eventDispatcher->dispatch(new BotUserUpdatedProfileEvent($this->botUser));

                $this->botSender->sendMessage(
                    $this->botUser,
                    'Спасибо! Мы проверим данные еще раз и сообщим вам о результатах!',
                    new ReplyKeyboardHide()
                );

                return RunResult::ok();
            }

            if ($reallyReady) {
                return RunResult::ok(self::NAME, true);
            }

            if ('Видео' === $text) {
                return RunResult::ok(ProfileUpdateVideoScreen::NAME);
            }

            if ('Фото' === $text) {
                return RunResult::ok(ProfileUpdatePhotoScreen::NAME);
            }

            if ('Телефон друга' === $text) {
                return RunResult::ok(ProfileUpdateFriendsPhoneScreen::NAME);
            }

            if ('Номер паспорта' === $text) {
                return RunResult::ok(ProfileUpdatePassportScreen::NAME);
            }

            if ('Фото документа' === $text) {
                return RunResult::ok(ProfileUpdateLicensePhotoScreen::NAME);
            }

            if ('Адрес' === $text) {
                return RunResult::ok(ProfileUpdateAddressScreen::NAME);
            }

            if ('Возраст' === $text) {
                return RunResult::ok(ProfileUpdateAgeScreen::NAME);
            }

            if ('Имя' === $text) {
                return RunResult::ok(ProfileUpdateNameScreen::NAME);
            }

            if ('Готовы иметь при себе 2000 рублей?' === $text) {
                return RunResult::ok(ProfileUpdateReadyScreen::NAME);
            }
        }

        $message = $this->buildMessage();
        $keyboard = $this->buildKeyboard();

        $this->botSender->sendMessage($this->botUser, $message, $keyboard);

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

    private function buildKeyboard(): ReplyKeyboardMarkup
    {
        $keyboardArray = [];

        $flippedMap = array_flip(self::$map);
        $updateRequiredFieldKeys = $this->getUpdateRequiredFieldKeys();

        foreach ($updateRequiredFieldKeys as $val) {
            $keyboardArray[] = [[
                'text' => $flippedMap[$val],
            ]];
        }

        $keyboardArray[] = [[
            'text' => self::READY_BUTTON,
        ]];

        return new ReplyKeyboardMarkup($keyboardArray, true, true);
    }

    private function getUpdateRequiredFieldKeys(): array
    {
        $updateRequiredFieldKeys = [];

        $correctFields = $this->botUser->getParameters()->getCorrectFields()->toArray();
        $updatedFields = $this->botUser->getParameters()->getUpdatedFields()->toArray();

        foreach ($correctFields as $key => $val) {
            if (!(bool) $val && !(bool) $updatedFields[$key]) {
                $updateRequiredFieldKeys[] = $key;
            }
        }

        return $updateRequiredFieldKeys;
    }

    public function buildMessage(): string
    {
        $updateRequiredFieldKeys = $this->getUpdateRequiredFieldKeys();
        if (0 === \count($updateRequiredFieldKeys)) {
            $message = 'Вы внесли все нужные изменения. Нажмите Готово чтобы повторно отправить на модерацию!';

            return $message;
        }

        $fieldComments = $this->botUser->getParameters()->getFieldComments()->toArray();

        $flippedMap = array_flip(self::$map);

        $message = file_get_contents(__DIR__.'/../../../../../Resources/bot_message/profile_update.txt');
        $message .= PHP_EOL.PHP_EOL.'Вам нужно изменить следующие поля:';

        foreach ($updateRequiredFieldKeys as $key => $val) {
            if (!isset($flippedMap[$val])) {
                continue;
            }

            $message .= sprintf(PHP_EOL.'- %s', $flippedMap[$val]);
            if (isset($fieldComments[$val]) && mb_strlen($fieldComments[$val]) > 0) {
                $message .= sprintf(': <i>%s</i>', $fieldComments[$val]);
            }
        }

        return $message;
    }
}
