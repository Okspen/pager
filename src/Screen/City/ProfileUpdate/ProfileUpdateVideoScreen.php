<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City\ProfileUpdate;

use App\Service\Bot\Screen\City\BaseCityScreen;
use App\Service\Bot\Screen\RunResult;
use App\Service\Bot\Service\ProfileVideoExampleSender;
use App\Service\Bot\Service\ProfileVideoSaver;
use App\Service\Bot\Validator\VideoValidator;
use App\Service\NgrokUrlGenerator;
use TelegramBot\Api\Types\Update;

class ProfileUpdateVideoScreen extends BaseCityScreen
{
    public const NAME = 'city.profile.update.video';

    /** @var NgrokUrlGenerator */
    private $ngrokUrlGenerator;

    /** @var ProfileVideoSaver */
    private $profileVideoSaver;

    /** @var ProfileVideoExampleSender */
    private $profileVideoExampleSender;

    public function __construct(NgrokUrlGenerator $ngrokUrlGenerator, ProfileVideoSaver $profileVideoSaver, ProfileVideoExampleSender $profileVideoExampleSender)
    {
        $this->ngrokUrlGenerator = $ngrokUrlGenerator;
        $this->profileVideoSaver = $profileVideoSaver;
        $this->profileVideoExampleSender = $profileVideoExampleSender;
    }

    public function run(): RunResult
    {
        if (!$this->firstVisit) {
            $video = $this->update->getMessage()->getVideo();
            $validationResult = VideoValidator::validate($video);

            if (false === $validationResult->ok) {
                $this->botSender->sendMessage(
                    $this->botUser,
                    $validationResult->errorMessage
                );

                return RunResult::ok();
            }

            $this->profileVideoSaver->save(
                $this->botSender,
                $this->botUser,
                $video
            );

            $this->botSender->sendMessage($this->botUser, 'Спасибо!');

            $parameters = $this->botUser->getParameters();

            $fieldComments = $parameters->getFieldComments();
            $updatedFields = $parameters->getUpdatedFields();

            $fieldComments->profileVideoPath = null;
            $updatedFields->profileVideoPath = true;

            $parameters->setFieldComments($fieldComments);
            $parameters->setUpdatedFields($updatedFields);

            $this->botUser->setParameters($parameters);

            return RunResult::ok(ProfileUpdateScreen::NAME, true);
        }

        $message = file_get_contents(__DIR__.'/../../../../../Resources/bot_message/profile_update_video.txt');
        $this->botSender->sendMessage($this->botUser, $message);

        $this->profileVideoExampleSender->sendExample($this->botSender, $this->botUser);

        return RunResult::ok();
    }

    public function addRoute(?Update $update = null): ?string
    {
        return self::NAME;
    }
}
