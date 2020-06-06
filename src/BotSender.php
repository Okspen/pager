<?php

declare(cstrict_types=1);

namespace Pager\Service\Bot;

use App\Entity\File;
use App\Event\BotUserBlockedBotEvent;
use App\Service\Bot\Service\RetryCaller;
use App\Service\NgrokUrlGenerator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use TelegramBot\Api\BaseType;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;

class BotSender implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var BotApi */
    protected $botApi;

    /** @var NgrokUrlGenerator */
    private $ngrokUrlGenerator;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        NgrokUrlGenerator $urlGenerator,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->ngrokUrlGenerator = $urlGenerator;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed|null
     *
     * @throws \TelegramBot\Api\HttpException
     */
    public function __call($name, $arguments)
    {
        try {
            return RetryCaller::call(function () use ($name, $arguments) {
                return \call_user_func_array([$this->botApi, $name], $arguments);
            });
        } catch (\Throwable $e) {
            $this->onException($e);

            return false;
        }
    }

    public function initWithToken(string $token): void
    {
        $this->botApi = new BotApi($token);
    }

    public function answerCallback(string $id, string $message = null, bool $showAlert = false): bool
    {
        try {
            RetryCaller::call(function () use ($id, $message, $showAlert): bool {
                return $this->botApi->answerCallbackQuery($id, $message, $showAlert);
            });
        } catch (\Throwable $e) {
            $this->onException($e);

            return false;
        }

        return true;
    }

    public function sendMessage(BotUserInterface $botUser, string $message, ?BaseType $keyboard = null): bool
    {
        try {
            RetryCaller::call(function () use ($botUser, $message, $keyboard): Message {
                return $this->botApi->sendMessage(
                    $botUser->getUserId(),
                    $message,
                    'HTML',
                    false,
                    null,
                    $keyboard
                );
            });
        } catch (\Throwable $e) {
            $this->onException($e, $botUser);

            return false;
        }

        return true;
    }

    public function editMessage(BotUserInterface $botUser, int $messageId, string $message, BaseType $keyboard = null): bool
    {
        try {
            RetryCaller::call(function () use ($botUser, $messageId, $message, $keyboard): Message {
                return $this->botApi->editMessageText(
                    $botUser->getUserId(),
                    $messageId,
                    $message,
                    'HTML',
                    false,
                    $keyboard
                );
            });
        } catch (\Throwable $e) {
            $this->onException($e, $botUser);

            return false;
        }

        return true;
    }

    public function sendContact(BotUserInterface $botUser, string $phone, string $firstName, string $lastName = null): bool
    {
        try {
            RetryCaller::call(function () use ($botUser, $phone, $firstName, $lastName): Message {
                return $this->botApi->sendContact(
                    $botUser->getUserId(),
                    $phone,
                    $firstName,
                    $lastName
                );
            });
        } catch (\Throwable $e) {
            $this->onException($e, $botUser);

            return false;
        }

        return true;
    }

    public function sendPhoto(BotUserInterface $botUser, string $fileUrl): bool
    {
        try {
            RetryCaller::call(function () use ($botUser, $fileUrl): Message {
                return $this->botApi->sendPhoto($botUser->getUserId(), $fileUrl);
            });
        } catch (\Throwable $e) {
            $this->onException($e, $botUser);

            return false;
        }

        return true;
    }

    public function sendVideo(BotUserInterface $botUser, $video): ?Message
    {
        try {
            return RetryCaller::call(function () use ($botUser, $video): Message {
                return $this->botApi->sendVideo($botUser->getUserId(), $video);
            });
        } catch (\Throwable $e) {
            $this->onException($e, $botUser);

            return null;
        }
    }

    public function sendFile(BotUserInterface $botUser, File $file): bool
    {
        /** @var File $file */
        $filePath = __DIR__.'/../../../public'.$file->getPath();
        if (false === file_exists($filePath)) {
            return false;
        }

        $mimeType = mime_content_type($filePath);
        $fileUrl = $this->ngrokUrlGenerator->getWebsiteUrl().$file->getPath();

        try {
            if ($file->isImage()) {
                $this->botApi->sendPhoto(
                    $botUser->getUserId(),
                    $fileUrl
                );
            } elseif ($file->isAudio()) {
                $this->botApi->sendVoice(
                    $botUser->getUserId(),
                    $fileUrl
                );
            } elseif ($file->isVideo()) {
                $this->botApi->sendVideo(
                    $botUser->getUserId(),
                    new \CURLFile($filePath, $mimeType)
                );
            } else {
                $this->botApi->sendDocument(
                    $botUser->getUserId(),
                    $fileUrl
                );
            }
        } catch (\Throwable $e) {
            $this->onException($e, $botUser);

            return false;
        }

        return true;
    }

    public function sendChatAction(BotUserInterface $botUser, string $action): bool
    {
        try {
            return (bool) RetryCaller::call(function () use ($botUser, $action): bool {
                return $this->botApi->sendChatAction($botUser->getUserId(), $action);
            });
        } catch (\Throwable $e) {
            $this->onException($e, $botUser);

            return false;
        }
    }

    private function onException(\Throwable $e, BotUserInterface $botUser = null): void
    {
        $this->logger->error($e->getMessage(), ['exception' => $e]);

        if (null !== $botUser && false !== mb_stripos($e->getMessage(), 'Forbidden: bot was blocked by the user')) {
            $this->eventDispatcher->dispatch(new BotUserBlockedBotEvent($botUser));
        }
    }
}
