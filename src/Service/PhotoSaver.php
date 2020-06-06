<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Service;

use Pager\Entity\BotUser;
use Pager\Service\Bot\BotSender;
use Imagine\Image\AbstractImagine;
use Symfony\Component\Filesystem\Filesystem;

class PhotoSaver
{
    /** @var AbstractImagine */
    private $imagine;

    /** @var string */
    private $publicPath;

    public const PROFILE_PHOTO_DIRECTORY_PATH = '/uploads/profile-photos';

    public function __construct(AbstractImagine $imagine, string $publicPath)
    {
        $this->imagine = $imagine;
        $this->publicPath = $publicPath;
    }

    public function saveProfile(BotSender $botSender, BotUser $botUser, string $filePath): void
    {
        $fileUrl = sprintf('%s/%s', $botSender->getFileUrl(), $filePath);
        $photoName = sprintf('%s-profile.%s', $botUser->getUserId(), pathinfo($filePath)['extension']);

        $this->saveFile($photoName, $fileUrl);

        $publicFilePath = self::PROFILE_PHOTO_DIRECTORY_PATH.'/'.$photoName;
        $botUser->setProfilePhotoPath($publicFilePath);
    }

    public function saveLicense(BotSender $botSender, BotUser $botUser, string $filePath): void
    {
        $fileUrl = sprintf('%s/%s', $botSender->getFileUrl(), $filePath);
        $photoName = sprintf('%s-license.%s', $botUser->getUserId(), pathinfo($filePath)['extension']);

        $this->saveFile($photoName, $fileUrl);

        $publicFilePath = self::PROFILE_PHOTO_DIRECTORY_PATH.'/'.$photoName;
        $botUser->setLicensePhotoPath($publicFilePath);
    }

    private function getDestinationDirectoryPath(): string
    {
        return $this->publicPath.self::PROFILE_PHOTO_DIRECTORY_PATH;
    }

    private function saveFile(string $fileName, string $fileUrl): void
    {
        $destinationDirectoryPath = $this->getDestinationDirectoryPath();

        $fs = new Filesystem();
        $fs->mkdir($destinationDirectoryPath);

        $fileContents = file_get_contents($fileUrl);
        $destinationFilePath = $destinationDirectoryPath.'/'.$fileName;

        file_put_contents($destinationFilePath, $fileContents);
    }

    public function flipHorizontally(BotUser $botUser): void
    {
        $profilePhotoPath = $botUser->getProfilePhotoPath();
        if (null === $profilePhotoPath) {
            return;
        }

        $filePath = $this->publicPath.$profilePhotoPath;
        $image = $this->imagine->open($filePath);

        $image
            ->flipHorizontally()
            ->save();
    }
}
