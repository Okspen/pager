<?php

declare(cstrict_types=1);

namespace Pager;

use App\Entity\File;
use App\Event\BotUserBlockedBotEvent;
use App\Service\NgrokUrlGenerator;
use TelegramBot\Api\BaseType;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;

/**
 * Interface BotSenderInterface
 * @package Pager
 */
interface BotSenderInterface
{
    public function answerCallback(string $id, string $message = null, bool $showAlert = false);

    public function sendMessage(BotUserInterface $botUser, string $message, ?BaseType $keyboard = null);

    public function editMessage(BotUserInterface $botUser, int $messageId, string $message, BaseType $keyboard = null);

    public function sendContact(BotUserInterface $botUser, string $phone, string $firstName, string $lastName = null);

    public function sendPhoto(BotUserInterface $botUser, string $fileUrl);

    public function sendVideo(BotUserInterface $botUser, $video);

    public function sendFile(BotUserInterface $botUser, File $file);

    public function sendChatAction(BotUserInterface $botUser, string $action);
}
