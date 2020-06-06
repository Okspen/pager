<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Service;

use Pager\Entity\BotUser;
use Pager\Entity\Enum\SettingKeyEnum;
use Pager\Repository\SettingRepository;
use Pager\Service\Bot\BotSender;
use Pager\Service\NgrokUrlGenerator;

class ProfileVideoExampleSender
{
    /** @var SettingRepository */
    private $settingRepository;

    /** @var NgrokUrlGenerator */
    private $urlGenerator;

    public function __construct(SettingRepository $settingRepository, NgrokUrlGenerator $urlGenerator)
    {
        $this->settingRepository = $settingRepository;
        $this->urlGenerator = $urlGenerator;
    }

    public function sendExample(BotSender $botSender, BotUser $botUser): void
    {
        $settingKey = sprintf('%s_%s', SettingKeyEnum::PROFILE_VIDEO_EXAMPLE_FILE_ID, $botUser->getCity()->getId());
        $setting = $this->settingRepository->findOrCreate($settingKey);
        if (null !== $setting->value) {
            $result = $botSender->sendVideo($botUser, $setting->value);
            // if video sent successfully
            if (null !== $result) {
                return;
            }
        }

        $profileVideoExampleUrl = $this->urlGenerator->getWebsiteUrl().'/assets/vid/contact_profile_video_1.mp4';

        $message = $botSender->sendVideo(
            $botUser,
            $profileVideoExampleUrl
        );

        if (null !== $message) {
            $video = $message->getVideo();
            if (null !== $video) {
                $setting->value = $video->getFileId();
                $this->settingRepository->save($setting);
            }
        }
    }
}
