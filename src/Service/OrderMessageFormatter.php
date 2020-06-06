<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Service;

use Pager\Entity\DeliveryDetails;
use Pager\Entity\Order;

class OrderMessageFormatter
{
    /** @var Order */
    private $order;

    /** @var float|null */
    private $distance;

    /** @var array */
    private $lines = [];

    public function __construct(Order $order, ?float $distance = null)
    {
        $this->order = $order;
        $this->distance = $distance;
    }

    public function __toString()
    {
        $this->lines = [];

        $this->lines[] = sprintf('Заказ %s - <b>%s</b>', $this->order->getId(), $this->order->getName());
        $this->lines[] = PHP_EOL;
        $this->lines[] = sprintf('<i>Подъехать к:</i> %s', $this->order->getBranchArrivalDateTime()->format('H:i'));
        if (null !== $this->order->getRequirements() && '' !== $this->order->getRequirements()) {
            $this->lines[] = sprintf('<i>Требования:</i> %s', $this->order->getRequirements());
        }
        $this->lines[] = sprintf('<i>Куда подъехать:</i> %s', null === $this->order->getBranch()->getAddress() ? '-' : $this->order->getBranch()->getAddress());
        if (null !== $this->distance) {
            $this->lines[] = sprintf('<i>%s км от вас</i>', round($this->distance * 100, 2));
        }
        $this->lines[] = PHP_EOL;

        foreach ($this->order->getDeliveries() as $key => $deliveryDetails) {
            $this->formatDeliveryAddress($deliveryDetails, (int) $key);
        }

        $this->lines[] = sprintf('Вы получаете за доставку: %s', $this->order->getCarrierAmount());
        $this->lines[] = sprintf('Cумма залога: %s', $this->order->getDepositAmount());
        $this->lines[] = sprintf('Cумма заказа: %s', $this->order->getTotalAmount());

        return implode(PHP_EOL, $this->lines);
    }

    private function formatDeliveryAddress(DeliveryDetails $details, int $index = 0): void
    {
        if ($this->order->getDeliveries()->count() > 1) {
            $this->lines[] = sprintf('Клиент %s', $index + 1);
        } else {
            $this->lines[] = 'Клиент';
        }

        $this->lines[] = '<i>В заказе:</i>';
        $this->lines[] = $details->getContent();

        $address = $details->getDetails();
        $this->lines[] = sprintf('<i>Адрес:</i> %s, %s', $address->street, $address->buildingNo);
        $this->lines[] = PHP_EOL;
    }
}
