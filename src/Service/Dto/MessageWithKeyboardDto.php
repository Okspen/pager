<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Service\Dto;

class MessageWithKeyboardDto
{
    /** @var string */
    public $message;

    /** @var array */
    public $keyboardData;
}
