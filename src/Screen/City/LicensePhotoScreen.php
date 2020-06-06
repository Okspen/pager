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

class LicensePhotoScreen extends BaseCityScreen
{
    public const NAME = 'city.profile.license.photo';

    /** @var NgrokUrlGenerator */
    private $ngrokUrlGenerator;

    /** @var PhotoSaver */
    private $photoSaver;

    public function __construct(NgrokUrlGenerator $ngrokUrlGenerator, PhotoSaver $photoSaver)
    {
        $this->ngrokUrlGenerator = $ngrokUrlGenerator;
        $this->photoSaver = $photoSaver;
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

            $this->photoSaver->saveLicense($this->botSender, $this->botUser, $file->getFilePath());

            return RunResult::ok(ProfileVideoScreen::NAME, true);
            //return RunResult::ok(ProfilePhotoScreen::NAME, true);
        }

        $this->botSender->sendMessage(
            $this->botUser,
            file_get_contents(__DIR__.'/../../../../Resources/bot_message/contact_license_photo.txt')
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
