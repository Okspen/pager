<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Service;

use App\Entity\News;
use App\Service\Bot\Service\Dto\MessageWithKeyboardDto;

class NewsKeyboardBuilder
{
    /** @var News */
    private $news;

    public function __construct(News $news)
    {
        $this->news = $news;
    }

    public function getSeenKeyboard(): MessageWithKeyboardDto
    {
        $mwk = new MessageWithKeyboardDto();
        $mwk->keyboardData = [[[
            'text' => 'Ознакомился',
            'callback_data' => sprintf('/seen-news %s', $this->news->getId()),
        ]]];

        return $mwk;
    }
}
