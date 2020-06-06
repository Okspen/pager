<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City;

use App\Entity\Enum\BotUserStatus;
use App\Service\Bot\Screen\RunResult;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use TelegramBot\Api\Types\Update;

class StartScreen extends BaseCityScreen
{
    public const NAME = 'city.start';

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function addRoute(?Update $update = null): ?string
    {
        return self::NAME;
    }

    public function run(): RunResult
    {
        if ($this->botUser->getStatus()->equals(BotUserStatus::APPROVED())) {
            return RunResult::ok(OrderScreen::NAME, true);
        }

        return RunResult::ok(JoinScreen::NAME);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
