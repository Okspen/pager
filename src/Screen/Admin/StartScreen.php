<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Screen\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Bot\Screen\AdminBotUserAwareTrait;
use App\Service\Bot\Screen\BaseScreen;
use App\Service\Bot\Screen\RunResult;
use TelegramBot\Api\Types\Update;

class StartScreen extends BaseScreen
{
    use AdminBotUserAwareTrait;

    public const NAME = 'admin.start';

    /** @var UserRepository */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function run(): RunResult
    {
        if (null === $this->botUser->getPhone()) {
            return RunResult::ok(ShareContactScreen::NAME, true);
        }

        if (!$this->firstVisit) {
            $authCode = $this->update->getMessage()->getText();

            /** @var User $user */
            $user = $this->userRepository->findOneByAuthCode($authCode);
            if (null === $user) {
                $this->botSender->sendMessage($this->botUser, 'Код авторизации не найден');

                return RunResult::ok(self::NAME, true);
            }

            if ($user->isAdminBotUserLinked($this->botUser)) {
                $this->botSender->sendMessage($this->botUser, sprintf('Ваш аккаунт уже привязан к филиалу %s (%s)', $user->getName(), $user->getPhone()));

                return RunResult::ok(self::NAME, true);
            }

            $user->linkAdminBotUser($this->botUser);
            $user->eraseAuthCode();
            $this->userRepository->save($user);

            $this->sendSuccessMessage($user);

            return RunResult::ok();
        }

        $this->botSender->sendMessage(
            $this->botUser,
            'Для авторизации отправьте, пожалуйста, код'
        );

        return RunResult::ok();
    }

    /**
     * This method is supposed to return the route for update.
     * The following bot workflow should be based on this specific route.
     */
    public function addRoute(?Update $update = null): ?string
    {
        return self::NAME;
    }

    private function sendSuccessMessage(User $user): void
    {
        $message = sprintf('Вы успешно авторизованы как <b>%s</b>!', $user->getName());
        $message .= PHP_EOL.PHP_EOL.'Здесь вы будете получать уведомления о состоянии заказов и другие полезные вещи';

        $this->botSender->sendMessage(
            $this->botUser,
            $message
        );
    }
}
