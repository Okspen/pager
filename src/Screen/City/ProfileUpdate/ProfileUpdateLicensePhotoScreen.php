<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City\ProfileUpdate;

use Pager\Service\Bot\Screen\City\BaseCityScreen;
use Pager\Service\Bot\Screen\RunResult;
use Pager\Service\Bot\Service\PhotoSaver;
use Pager\Service\Bot\Validator\PhotoValidator;
use Pager\Service\NgrokUrlGenerator;
use TelegramBot\Api\Types\File;
use TelegramBot\Api\Types\PhotoSize;
use TelegramBot\Api\Types\Update;

class ProfileUpdateLicensePhotoScreen extends BaseCityScreen
{
    public const NAME = 'city.profile.update.license.photo';

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
        if (!$this->firstVisit) {
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

            $this->profilePhotoSaver->saveLicense($this->botSender, $this->botUser, $file->getFilePath());

            $this->botSender->sendMessage($this->botUser, 'Спасибо!');

            $parameters = $this->botUser->getParameters();

            $fieldComments = $parameters->getFieldComments();
            $updatedFields = $parameters->getUpdatedFields();

            $fieldComments->licensePhotoPath = null;
            $updatedFields->licensePhotoPath = true;

            $parameters->setFieldComments($fieldComments);
            $parameters->setUpdatedFields($updatedFields);

            $this->botUser->setParameters($parameters);

            return RunResult::ok(ProfileUpdateScreen::NAME, true);
        }

        $message = file_get_contents(__DIR__.'/../../../../../Resources/bot_message/contact_license_photo.txt');
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
