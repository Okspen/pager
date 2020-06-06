<?php

declare(strict_types=1);

namespace Pager\Service\Bot;

use Pager\Entity\Enum\SettingKeyEnum;
use Pager\Repository\SettingRepository;
use Pager\Service\NgrokUrlGenerator;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TelegramBot\Api\BaseType;

class AdminBotSender extends BotSender
{
    use LoggerAwareTrait;

    /** @var SettingRepository */
    private $settingRepository;

    /** @var string|null */
    private $token;

    public function __construct(
        SettingRepository $settingRepository,
        NgrokUrlGenerator $urlGenerator,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->settingRepository = $settingRepository;

        parent::__construct($urlGenerator, $eventDispatcher);
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function init(): void
    {
        $setting = $this->settingRepository->find(SettingKeyEnum::ADMIN_BOT_TOKEN);
        if (null === $setting) {
            $this->logger->error('Could not initialize AdminBotSender: token missing in settings repository');

            return;
        }

        $this->token = $setting->value;
        $this->initWithToken($this->token);
    }

    public function sendNotification(string $message, ?BaseType $keyboard = null): bool
    {
        try {
            $this->botApi->sendMessage(
                $this->settingRepository->find(SettingKeyEnum::NOTIFICATION_CHANNEL_ID)->value,
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
