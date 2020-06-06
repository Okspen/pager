<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Parts;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\Bot\Exception\OrderNotFound;

trait OrderAwareHandlerTrait
{
    /** @var int|null */
    protected $orderId;

    /** @var OrderRepository|null */
    protected $orderRepository;

    protected function getOrder(): ?Order
    {
        $orderId = $this->orderId ?? null;
        if (null === $orderId) {
            return null;
        }

        /** @var Order|null $order */
        $order = $this->orderRepository->find((int) $orderId);
        if (null === $order) {
            return null;
        }

//        if ($order->getBranch()->getCity()->getId() !== $this->botUser->getCity()->getId()) {
//            return null;
//        }

        return $order;
    }

    protected function assertOrder(): void
    {
        if (null === $this->getOrder()) {
            throw new OrderNotFound($this->callbackData->arguments[0]);
        }
    }
}
