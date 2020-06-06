<?php

declare(strict_types=1);

namespace Pager\Service\Bot;

use TelegramBot\Api\Types\ReplyKeyboardMarkup;

class KeyboardBuilder
{
    public static function build(array $replyMarkup, bool $withMainButton = false, bool $withBackButton = false, bool $withCommentButton = false): ReplyKeyboardMarkup
    {
        if ($withCommentButton) {
            $replyMarkup[] = [ButtonNames::WRITE_COMMENT];
        }

        if ($withMainButton) {
            $replyMarkup[] = [ButtonNames::MAIN];
        }

        if ($withBackButton) {
            $replyMarkup[] = [ButtonNames::BACK];
        }

        return new ReplyKeyboardMarkup($replyMarkup, false, true);
    }

    public static function buildWithBackButton(array $replyMarkup): ReplyKeyboardMarkup
    {
        $replyMarkup[] = [ButtonNames::BACK];

        return new ReplyKeyboardMarkup($replyMarkup, false, true);
    }
}
