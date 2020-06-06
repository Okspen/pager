<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Service;

use Pager\Service\Bot\Service\Dto\MessageWithKeyboardDto;

class ApprovedMessageBuilder
{
    public function build(): MessageWithKeyboardDto
    {
        $message = file_get_contents(__DIR__.'/../../../Resources/bot_message/contact_approved.txt');

        $mwk = new MessageWithKeyboardDto();
        $mwk->message = $message;
        $mwk->keyboardData = [
            [
                [
                    'text' => 'Правила работы',
                ],
            ],
            [
                [
                    'text' => 'Начать работу',
                ],
            ],
        ];

        return $mwk;
    }
}
