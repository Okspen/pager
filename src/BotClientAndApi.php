<?php

declare(strict_types=1);

namespace Pager\Service\Bot;

use Pager\Entity\Enum\SettingKeyEnum;
use Pager\Repository\SettingRepository;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client;

class BotClientAndApi
{
    /** @var Client */
    private $client;

    /** @var BotApi */
    private $api;

    /** @var BotSender */
    private $sender;

    /** @var SettingRepository */
    private $settingRepository;

    public function __construct(SettingRepository $settingRepository, BotSender $botSender)
    {
        $this->settingRepository = $settingRepository;
        $this->sender = $botSender;
    }

    public function init(): void
    {
        if (false === (null === $this->client || null === $this->api)) {
            return;
        }

        // admin bot here
        $token = $this->settingRepository->find(SettingKeyEnum::NOTIFICATION_BOT_TOKEN);
        if (null === $token) {
            return;
        }

        $this->client = new Client($token->value);
        $this->api = new BotApi($token->value);
        $this->sender->initWithToken($token->value);
    }

    public function getApi(): BotApi
    {
        return $this->api;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getSender(): BotSender
    {
        return $this->sender;
    }
}
