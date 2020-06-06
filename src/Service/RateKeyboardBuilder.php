<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Service;

use App\Entity\Order;
use App\Service\Bot\Service\Dto\MessageWithKeyboardDto;

class RateKeyboardBuilder
{
    private $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function create(): MessageWithKeyboardDto
    {
        $mwk = new MessageWithKeyboardDto();
        $mwk->message = sprintf('Расскажите, как отработал курьер %s на заказе %s?', $this->order->getApprovedBotUser()->getName(), $this->order->getName());
        $mwk->keyboardData = $this->getKeyboard();

        return $mwk;
    }

    private function getKeyboard(): array
    {
        $keyboard = [];

        for ($i = 5; $i >= 1; --$i) {
            $iString = (string) $i;
            $keyboard[] = [[
                'text' => $iString,
                'callback_data' => sprintf('/rate %s %s %s', $this->order->getId(), $this->order->getApprovedBotUser()->getId(), $iString),
            ]];
        }

        return $keyboard;
    }
}
