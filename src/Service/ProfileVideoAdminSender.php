<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Service;

use App\Entity\AdminBotUser;
use App\Entity\BotUser;
use App\Repository\BotUserRepository;
use App\Service\Bot\AdminBotSender;
use App\Service\NgrokUrlGenerator;

class ProfileVideoAdminSender
{
    /** @var AdminBotSender */
    private $adminBotSender;

    /** @var BotUserRepository */
    private $botUserRepository;

    /** @var NgrokUrlGenerator */
    private $urlGenerator;

    public function __construct(AdminBotSender $adminBotSender, BotUserRepository $botUserRepository, NgrokUrlGenerator $urlGenerator)
    {
        $this->adminBotSender = $adminBotSender;
        $this->botUserRepository = $botUserRepository;
        $this->urlGenerator = $urlGenerator;
    }

    public function sendProfileVideo(AdminBotUser $adminBotUser, BotUser $botUser): void
    {
        $fileId = $botUser->getProfileVideoAdminFileId();

        if (null !== $fileId) {
            $this->adminBotSender->sendVideo($adminBotUser, $fileId);

            return;
        }

        if (null === $botUser->getProfileVideoPath() || 0 === mb_strlen($botUser->getProfileVideoPath())) {
            return;
        }

        $profileVideoUrl = $this->urlGenerator->getWebsiteUrl().$botUser->getProfileVideoPath();

        $message = $this->adminBotSender->sendVideo(
            $adminBotUser,
            $profileVideoUrl
        );

        if (null !== $message) {
            $video = $message->getVideo();
            if (null !== $video) {
                $botUser->setProfileVideoAdminFileId($video->getFileId());
                $this->botUserRepository->save($botUser);
            }
        }
    }
}
