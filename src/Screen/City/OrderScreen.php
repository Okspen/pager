<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City;

use App\Entity\Enum\BotUserStatus;
use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\Bot\Screen\RunResult;
use App\Service\Bot\Service\OrderBotUserKeyboardBuilder;
use App\Service\Bot\Service\OrderMessageBuilder;
use CrEOF\Spatial\PHP\Types\Geography\Point;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class OrderScreen extends BaseCityScreen implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const NAME = 'city.order';

    /** @var OrderRepository */
    private $orderRepository;

    /** @var OrderMessageBuilder */
    private $orderMessageBuilder;

    public function __construct(OrderRepository $orderRepository, OrderMessageBuilder $orderMessageBuilder)
    {
        $this->orderRepository = $orderRepository;
        $this->orderMessageBuilder = $orderMessageBuilder;
    }

    public function run(): RunResult
    {
        if (false === $this->botUser->getStatus()->equals(BotUserStatus::APPROVED())) {
            $this->botSender->sendMessage($this->botUser, 'Перед получением заказов необходимо пройти модерацию');

            return RunResult::ok(JoinScreen::NAME, true);
        }

        // in case we get list from accept or decline button
        if (null !== $this->update->getMessage()) {
            $location = $this->update->getMessage()->getLocation();
        } else {
            $location = $this->botUser->getLastLocation();
        }

        if (null !== $location) {
            $point = new Point($location->getLongitude(), $location->getLatitude());

            $this->botUser->setLastLocation($point);
            $orders = $this->orderRepository->findNearby($this->botUser, $point);

            $this->sendOrderMessages($orders);

            return RunResult::ok();
        }

        $mwk = (new OrderBotUserKeyboardBuilder())->sendGeolocation();

        $this->botSender->sendMessage(
            $this->botUser,
            $mwk->message,
            new ReplyKeyboardMarkup($mwk->keyboardData, false, true)
        );

        return RunResult::ok();
    }

    public function addRoute(?Update $update = null): ?string
    {
        return self::NAME;
    }

    private function sendOrderMessages(array $orders): void
    {
        $ordersWithDistance = array_filter($orders, function ($item): bool {
            return null !== $item['dist'];
        });

        if (0 === \count($ordersWithDistance)) {
            $this->botSender->sendMessage($this->botUser, 'Заказов пока нет');

            return;
        }

        foreach ($ordersWithDistance as $orderData) {
            try {
                /** @var Order $order */
                $order = $orderData[0];
                $this->orderMessageBuilder->init($orderData[0], (float) $orderData['dist']);
                if ($order->hasAccepted($this->botUser)) {
                    $mwk = $this->orderMessageBuilder->buildAcceptedOrderMessage();
                    $this->botSender->sendMessage(
                        $this->botUser,
                        $mwk->message
                    );
                } else {
                    $mwk = $this->orderMessageBuilder->buildOrderMessage();
                    $this->botSender->sendMessage(
                        $this->botUser,
                        $mwk->message,
                        new InlineKeyboardMarkup($mwk->keyboardData)
                    );
                }

                $order->addView($this->botUser);
            } catch (\Throwable $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
            }
        }
    }
}
