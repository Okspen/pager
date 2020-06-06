<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City;

use App\Event\BotUserUploadedProfileVideoEvent;
use App\Service\Bot\Screen\RunResult;
use App\Service\Bot\Service\ProfileVideoExampleSender;
use App\Service\Bot\Service\ProfileVideoSaver;
use App\Service\Bot\Validator\VideoValidator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use TelegramBot\Api\Types\Update;

class ProfileVideoScreen extends BaseCityScreen
{
    public const NAME = 'city.profile.video';

    /** @var ProfileVideoSaver */
    private $profileVideoSaver;

    /** @var ProfileVideoExampleSender */
    private $profileVideoExampleSender;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        ProfileVideoSaver $profileVideoSaver,
        ProfileVideoExampleSender $profileVideoExampleSender,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->profileVideoSaver = $profileVideoSaver;
        $this->profileVideoExampleSender = $profileVideoExampleSender;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function run(): RunResult
    {
        if (false === $this->firstVisit) {
            $video = $this->update->getMessage()->getVideo();
            $validationResult = VideoValidator::validate($video);

            if (false === $validationResult->ok) {
                $this->botSender->sendMessage(
                    $this->botUser,
                    $validationResult->errorMessage
                );

                return RunResult::ok(self::NAME, true);
            }

            $this->botSender->sendChatAction($this->botUser, 'typing');

            $this->profileVideoSaver->save(
                $this->botSender,
                $this->botUser,
                $video
            );

            $this->eventDispatcher->dispatch(new BotUserUploadedProfileVideoEvent($this->botUser));

            return RunResult::ok(FriendsPhoneScreen::NAME, true);
        }

        $this->botSender->sendMessage(
            $this->botUser,
            file_get_contents(__DIR__.'/../../../../Resources/bot_message/contact_profile_video.txt')
        );

        $this->botSender->sendChatAction($this->botUser, 'record_video');

        $this->profileVideoExampleSender->sendExample($this->botSender, $this->botUser);

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
