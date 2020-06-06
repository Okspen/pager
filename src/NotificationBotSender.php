<?php

declare(strict_types=1);

namespace Pager\Service\Bot;

use Pager\Entity\Enum\SettingKeyEnum;
use Pager\Repository\SettingRepository;
use Pager\Service\NgrokUrlGenerator;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TelegramBot\Api\BaseType;

class NotificationBotSender extends BotSender
{
    use LoggerAwareTrait;

    /** @var SettingRepository */
    private $settingRepository;

    /** @var string */
    private $notificationChannelId;

    /** @var string */
    private $feedbackChannelId;

    public function __construct(
        SettingRepository $settingRepository,
        NgrokUrlGenerator $urlGenerator,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->settingRepository = $settingRepository;

        parent::__construct($urlGenerator, $eventDispatcher);
    }

    public function init(): void
    {
        $values = $this->settingRepository->findByIndexWithCodes([
            SettingKeyEnum::NOTIFICATION_BOT_TOKEN,
            SettingKeyEnum::NOTIFICATION_CHANNEL_ID,
            SettingKeyEnum::FEEDBACK_CHANNEL_ID,
        ]);

        $this->initWithToken($values[SettingKeyEnum::NOTIFICATION_BOT_TOKEN]->value);
        $this->notificationChannelId = $values[SettingKeyEnum::NOTIFICATION_CHANNEL_ID]->value;
        $this->feedbackChannelId = $values[SettingKeyEnum::FEEDBACK_CHANNEL_ID]->value;
    }

    public function sendNotification(string $message, ?BaseType $keyboard = null): bool
    {
        try {
            $this->botApi->sendMessage(
                $this->notificationChannelId,
                $message,
                'HTML',
                null,
                null,
                $keyboard
            );
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return false;
        }

        return true;
    }

    public function sendFeedback(string $message, ?BaseType $keyboard = null): bool
    {
        try {
            $this->botApi->sendMessage(
                $this->feedbackChannelId,
                $message,
                'HTML',
                null,
                null,
                $keyboard
            );
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return false;
        }

        return true;
    }
}
