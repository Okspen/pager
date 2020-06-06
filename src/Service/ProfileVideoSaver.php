<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Service;

use Pager\Entity\BotUser;
use Pager\Service\Bot\BotSender;
use Symfony\Component\Filesystem\Filesystem;
use TelegramBot\Api\Types\File;
use TelegramBot\Api\Types\Video;

class ProfileVideoSaver
{
    /** @var string */
    private $publicPath;

    public function __construct(string $publicPath)
    {
        $this->publicPath = $publicPath;
    }

    public function save(BotSender $botSender, BotUser $botUser, Video $video): void
    {
        $fileId = $video->getFileId();
        if (null !== $fileId && mb_strlen($fileId) > 0) {
            $botUser->setProfileVideoFileId($fileId);
        }

        /** @var File $file */
        $file = $botSender->getFile($fileId);

        $filePath = $file->getFilePath();
        $fileUrl = sprintf('%s/%s', $botSender->getFileUrl(), $filePath);

        $videoName = sprintf(
            '%s-profile.%s',
            $botUser->getUserId(),
            pathinfo($filePath)['extension']
        );

        $profileVideoDirectoryPath = '/uploads/profile-videos';
        $destinationDirectoryPath = $this->publicPath.$profileVideoDirectoryPath;
        $destinationFilePath = $destinationDirectoryPath.'/'.$videoName;

        $fileContents = file_get_contents($fileUrl);

        $fs = new Filesystem();
        $fs->mkdir($destinationDirectoryPath);

        file_put_contents($destinationFilePath, $fileContents);

        $botUser->setProfileVideoPath($profileVideoDirectoryPath.'/'.$videoName);
    }
}
