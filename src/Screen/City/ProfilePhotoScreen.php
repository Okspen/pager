<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City;

use App\Service\Bot\Screen\RunResult;
use App\Service\Bot\Service\PhotoSaver;
use App\Service\Bot\Validator\PhotoValidator;
use App\Service\NgrokUrlGenerator;
use TelegramBot\Api\Types\File;
use TelegramBot\Api\Types\PhotoSize;
use TelegramBot\Api\Types\Update;

class ProfilePhotoScreen extends BaseCityScreen
{
    public const NAME = 'city.profile.photo';

    /** @var NgrokUrlGenerator */
    private $ngrokUrlGenerator;

    /** @var PhotoSaver */
    private $profilePhotoSaver;

    public function __construct(NgrokUrlGenerator $ngrokUrlGenerator, PhotoSaver $profilePhotoSaver)
    {
        $this->ngrokUrlGenerator = $ngrokUrlGenerator;
        $this->profilePhotoSaver = $profilePhotoSaver;
    }

    public function run(): RunResult
    {
        if (false === $this->firstVisit) {
            $photoSizeArray = $this->update->getMessage()->getPhoto();
            $validationResult = PhotoValidator::validate($photoSizeArray);

            if (false === $validationResult->ok) {
                $this->botSender->sendMessage(
                    $this->botUser,
                    $validationResult->errorMessage
                );

                return RunResult::ok(self::NAME, true);
            }

            /** @var PhotoSize $biggestPhotoSize */
            $biggestPhotoSize = $photoSizeArray[\count($photoSizeArray) - 1];

            /** @var File $file */
            $file = $this->botSender->getFile($biggestPhotoSize->getFileId());

            $this->profilePhotoSaver->saveProfile($this->botSender, $this->botUser, $file->getFilePath());

            return RunResult::ok(ProfileVideoScreen::NAME, true);
        }

        $this->botSender->sendMessage(
            $this->botUser,
            file_get_contents(__DIR__.'/../../../../Resources/bot_message/contact_profile_photo.txt')
        );

        $profilePhotoExampleUrl = $this->ngrokUrlGenerator->getWebsiteUrl().'/assets/img/contact_profile_photo_1.jpg';

        $this->botSender->sendPhoto(
            $this->botUser,
            $profilePhotoExampleUrl
        );

        return RunResult::ok();
    }

    public function addRoute(?Update $update = null): ?string
    {
        return self::NAME;
    }
}
