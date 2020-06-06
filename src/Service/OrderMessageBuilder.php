<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Service;

use App\Entity\Order;
use App\Service\Bot\Service\Dto\MessageWithKeyboardDto;
use App\Service\RuleService;

class OrderMessageBuilder
{
    /** @var Order */
    private $order;

    /** @var float|null */
    private $distance;

    /** @var RuleService */
    private $ruleService;

    public function __construct(RuleService $ruleService)
    {
        $this->ruleService = $ruleService;
    }

    public function init(Order $order, ?float $distance = null): self
    {
        $this->order = $order;
        $this->distance = $distance;

        return $this;
    }

    public function buildOrderMessage(): MessageWithKeyboardDto
    {
        $mwk = new MessageWithKeyboardDto();
        $mwk->message = (string) (new OrderMessageFormatter($this->order, $this->distance));
        $mwk->keyboardData = $this->getKeyboard();

        return $mwk;
    }

    public function buildAcceptedOrderMessage(): MessageWithKeyboardDto
    {
        $mwk = $this->buildOrderMessage();
        $mwk->message .= PHP_EOL.PHP_EOL.'<b>Заказ принят</b>';
        $mwk->keyboardData = [];

        return $mwk;
    }

    private function getKeyboard(): array
    {
        $data = [
            [
                [
                    'text' => 'Принять',
                    'callback_data' => sprintf('/accept-order %s', $this->order->getId()),
                ],
            ],
            [
                [
                    'text' => 'Отклонить',
                    'callback_data' => sprintf('/decline-order %s', $this->order->getId()),
                ],
            ],
        ];

        if ($this->ruleService->rulesExist()) {
            $data[] = [
                [
                    'text' => 'Правила',
                    'callback_data' => '/rules',
                ],
            ];
        }

        return $data;
    }

    public function getYesNoKeyboard(): array
    {
        $data = [
            [
                [
                    'text' => 'Да',
                    'callback_data' => sprintf('/accept-order %s yes', $this->order->getId()),
                ],
            ],
            [
                [
                    'text' => 'Нет',
                    'callback_data' => sprintf('/accept-order %s no', $this->order->getId()),
                ],
            ],
        ];

        return $data;
    }
}
