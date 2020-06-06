<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City;

use App\Entity\Enum\BotUserStatus;
use App\Event\BotUserCompletedQuestEvent;
use App\Service\Bot\Screen\RunResult;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class ReadyToHaveMoneyScreen extends BaseCityScreen
{
    public const NAME = 'city.ready.for.money';

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function run(): RunResult
    {
        if (false === $this->firstVisit) {
            $text = $this->update->getMessage()->getText();

            if (!\in_array($text, ['Да', 'Нет'], true)) {
                return RunResult::ok(self::NAME, true);
            }

            $this->botUser->setReadyForMoney('Да' === $text);

            $parameters = $this->botUser->getParameters();
            $parameters->resetPath();
            $parameters->joinQuestCompleted = true;

            $this->botUser->setParameters($parameters);
            $this->botUser->setStatus(BotUserStatus::NEW());

            $this->eventDispatcher->dispatch(new BotUserCompletedQuestEvent($this->botUser));

            return RunResult::ok(FinalScreen::NAME, true);
        }

        $keyboard = new ReplyKeyboardMarkup([['Да'], ['Нет']], true, true);

        $this->botSender->sendMessage(
            $this->botUser,
            file_get_contents(__DIR__.'/../../../../Resources/bot_message/contact_ready_for_money.txt'),
            $keyboard
        );

        return RunResult::ok();
    }

    /**
     * This function is supposed to return the route for update.
     * The following bot workflow should be based on this specific route.
     */
    public function addRoute(?Update $update = null): ?string
    {
        return self::NAME;
    }
}
