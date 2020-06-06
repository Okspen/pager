<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City\ProfileUpdate;

use App\Service\Bot\Screen\City\BaseCityScreen;
use App\Service\Bot\Screen\RunResult;
use App\Service\Bot\Service\PhotoSaver;
use App\Service\Bot\Validator\PhotoValidator;
use App\Service\NgrokUrlGenerator;
use TelegramBot\Api\Types\File;
use TelegramBot\Api\Types\PhotoSize;
use TelegramBot\Api\Types\Update;

class ProfileUpdatePhotoScreen extends BaseCityScreen
{
    public const NAME = 'city.profile.update.photo';

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

            $this->profilePhotoSaver->saveProfile($this->botSender, $this->botUser, $file->getFilePath());

            $this->botSender->sendMessage($this->botUser, 'Спасибо!');

            $parameters = $this->botUser->getParameters();

            $fieldComments = $parameters->getFieldComments();
            $updatedFields = $parameters->getUpdatedFields();

            $fieldComments->profilePhotoPath = null;
            $updatedFields->profilePhotoPath = true;

            $parameters->setFieldComments($fieldComments);
            $parameters->setUpdatedFields($updatedFields);

            $this->botUser->setParameters($parameters);

            return RunResult::ok(ProfileUpdateScreen::NAME, true);
        }

        $message = file_get_contents(__DIR__.'/../../../../../Resources/bot_message/profile_update_photo.txt');
        $this->botSender->sendMessage($this->botUser, $message);

        $profilePhotoExampleUrl = $this->ngrokUrlGenerator->getWebsiteUrl().'/assets/img/contact_profile_photo_1.jpg';
        $this->botSender->sendPhoto(
            $this->botUser,
            $profilePhotoExampleUrl
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
