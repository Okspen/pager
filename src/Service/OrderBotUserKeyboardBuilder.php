<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Service;

use App\Entity\BotUser;
use App\Entity\DeliveryDetails;
use App\Entity\Enum\DeliveryStatus;
use App\Entity\Order;
use App\Service\Bot\Service\Dto\MessageWithKeyboardDto;

class OrderBotUserKeyboardBuilder
{
    public function approvedCommon(Order $order, BotUser $botUser): MessageWithKeyboardDto
    {
        $message = sprintf('Поздравляем! Вас утвердили на заказ %s. %s', $order->getId(), $order->getName());
        $message .= PHP_EOL.PHP_EOL.'<b>Нажимайте, пожалуйста, кнопки состояния заказа!</b>'.PHP_EOL.'По нажатию Вам будет отправляться информация по заказу.';

        $clientKeyboardData = [
            [
                [
                    'text' => ($order->getRespondedBotUser($botUser)->hasLeft() ? '✔ ' : '').'Выехал',
                    'callback_data' => sprintf('/on-my-way %s %s', $order->getId(), $botUser->getId()),
                ],
                [
                    'text' => self::withCheckMark($order->getDeliveries()->first(), DeliveryStatus::ARRIVING(), 'Подъехал'),
                    'callback_data' => sprintf('/arriving %s %s', $order->getId(), $botUser->getId()),
                ],
            ],
        ];

        $messageWithKeyboard = new MessageWithKeyboardDto();
        $messageWithKeyboard->message = $message;
        $messageWithKeyboard->keyboardData = $clientKeyboardData;

        return $messageWithKeyboard;
    }

    public function delivery(Order $order, DeliveryDetails $deliveryDetails): MessageWithKeyboardDto
    {
        $clientKeyboardData = [];

        $clientKeyboardData[] = [
            [
                'text' => self::withCheckMark($deliveryDetails, DeliveryStatus::ARRIVED(), 'На месте'),
                'callback_data' => sprintf('/arrived %s %s', $order->getId(), $deliveryDetails->getId()),
            ],
        ];
        $clientKeyboardData[] = [
            [
                'text' => self::withCheckMark($deliveryDetails, DeliveryStatus::COMPLETED(), 'Заказ выполнен'),
                'callback_data' => sprintf('/completed %s %s', $order->getId(), $deliveryDetails->getId()),
            ],
            [
                'text' => self::withCheckMark($deliveryDetails, DeliveryStatus::HAVE_PROBLEM(), 'Возникла проблема'),
                'callback_data' => sprintf('/have-problem %s %s', $order->getId(), $deliveryDetails->getId()),
            ],
        ];

        $messageWithKeyboard = new MessageWithKeyboardDto();
        $messageWithKeyboard->message = sprintf('<i>Клиент</i> %s'.PHP_EOL.'<i>В заказе:</i>'.PHP_EOL.'%s', $deliveryDetails->getClientName(), $deliveryDetails->getContent());
        $messageWithKeyboard->keyboardData = $clientKeyboardData;

        return $messageWithKeyboard;
    }

    private static function withCheckMark(DeliveryDetails $deliveryDetails, DeliveryStatus $buttonStatus, string $text): string
    {
        $statusDatetimes = $deliveryDetails->getStatusDatetimes();

        return (isset($statusDatetimes[$buttonStatus->getValue()]) ? '✔ ' : '').$text;
    }

    public function sendGeolocation(): MessageWithKeyboardDto
    {
        $mwk = new MessageWithKeyboardDto();
        $mwk->message = 'Чтобы найти заказы, пришлите нам свою геолокацию, и мы покажем ближайшие к вам заказы!';
        $mwk->keyboardData = [[[
            'text' => 'Отправить геолокацию',
            'request_location' => true,
        ]]];

        return $mwk;
    }
}
