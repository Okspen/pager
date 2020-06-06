<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\City;

use App\Service\Bot\Screen\RunResult;
use App\Service\Bot\Service\ApprovedMessageBuilder;
use App\Service\RuleService;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;

class ApprovedScreen extends BaseCityScreen
{
    public const NAME = 'city.approved';

    /** @var RuleService */
    private $ruleService;

    public function __construct(RuleService $ruleService)
    {
        $this->ruleService = $ruleService;
    }

    public function run(): RunResult
    {
        if (!$this->firstVisit) {
            $message = $this->update->getMessage()->getText();
            if ('Правила работы' === $message) {
                if (!$this->ruleService->rulesExist()) {
                    $this->botSender->sendMessage($this->botUser, 'Правил пока нет');

                    return RunResult::ok();
                }

                $rules = $this->ruleService->getRules();
                $this->botSender->sendMessage($this->botUser, $rules);

                return RunResult::ok();
            }

            if ('Начать работу' === $message) {
                return RunResult::ok(StartScreen::NAME, true);
            }

            return RunResult::ok(self::NAME, true);
        }

        $builder = new ApprovedMessageBuilder();
        $mwk = $builder->build();
        $this->botSender->sendMessage($this->botUser, $mwk->message, new ReplyKeyboardMarkup($mwk->keyboardData, false, true));

        return RunResult::ok();
    }
}
