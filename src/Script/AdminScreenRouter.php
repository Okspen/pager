<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Script;

use App\Entity\AdminBotUser;
use App\Repository\AdminBotUserRepository;
use App\Repository\BotUserRepository;
use App\Service\Bot\BotUserInterface;
use App\Service\Bot\ButtonNames;
use App\Service\Bot\Parts\BotUserAwareInterface;
use App\Service\Bot\Parts\UpdateAwareInterface;
use App\Service\Bot\Screen\Admin\StartScreen;
use App\Service\Bot\Screen\City\JoinScreen;
use App\Service\Bot\Screen\ScreenInterface;
use Psr\Log\LoggerAwareTrait;
use Sylius\Component\Registry\ServiceRegistryInterface;
use TelegramBot\Api\Types\Update;

class AdminScreenRouter implements RouterInterface
{
    use LoggerAwareTrait;

    /** @var ServiceRegistryInterface */
    private $screenRegistry;

    /** @var BotUserRepository */
    private $adminBotUserRepository;

    public function __construct(
        ServiceRegistryInterface $screenRegistry,
        AdminBotUserRepository $botUserRepository
    ) {
        $this->adminBotUserRepository = $botUserRepository;
        $this->screenRegistry = $screenRegistry;
    }

    public function route(Update $update, BotUserInterface $botUser): ScreenInterface
    {
        $screenName = $this->routeAndReturnName($update, $botUser);

        $screen = $this->instantiatePreparedScreen($botUser, $screenName, $update);

        return $screen;
    }

    public function routeAndReturnName(Update $update, BotUserInterface $botUser): string
    {
        $screenName = null;

        $message = $update->getMessage();
        $screenName = $this->chooseScreenBasedOnMessageText($message->getText(), $botUser);

        // set default screen
        if (null === $screenName) {
            $screenName = StartScreen::NAME;
        }

        $this->logger->info(sprintf('Routed and selected route: %s', $screenName));

        return $screenName;
    }

    public function instantiatePreparedScreen(BotUserInterface $botUser, string $screenName, ?Update $update = null): ?ScreenInterface
    {
        /** @var ScreenInterface $screen */
        $screen = $this->screenRegistry->get($screenName);

        $this->prepare($screen, $botUser, $update);

        return $screen;
    }

    private function prepare(ScreenInterface &$screen, BotUserInterface $botUser, ?Update $update = null): void
    {
        $lastPath = $botUser->getParameters()->last();

        // get route and set it for next screen
        $newPath = $screen->addRoute($update);
        $this->addRoute($botUser, $newPath);

        $screen->setFirstVisit($lastPath !== $newPath);

        if ($screen instanceof BotUserAwareInterface) {
            $screen->setBotUser($this->resolveBotUser($botUser));
        }

        if ($screen instanceof UpdateAwareInterface && null !== $update) {
            $screen->setUpdate($update);
        }
    }

    private function chooseScreenBasedOnMessageText(?string $messageText, BotUserInterface $botUser): ?string
    {
        $screenName = null;

        switch ($messageText) {
            case '/start':
                $screenName = StartScreen::NAME;

                $parameters = $botUser->getParameters();
                $parameters->resetPath();
                $botUser->setParameters($parameters);

                $this->adminBotUserRepository->save($botUser);

                break;
            case ButtonNames::BACK:
                $parameters = $botUser->getParameters();
                $parameters->back();
                $botUser->setParameters($parameters);

                $this->adminBotUserRepository->save($botUser);

                $screenName = $botUser->getParameters()->lastScreen();

                if (null === $screenName) {
                    $screenName = StartScreen::NAME;
                }

                break;
            default:
                $screenName = $botUser->getParameters()->lastScreen();

                if (null === $screenName) {
                    $screenName = JoinScreen::NAME;
                }

                break;
        }

        return $screenName;
    }

    private function addRoute(BotUserInterface $botUser, ?string $route): void
    {
        if (null === $route) {
            return;
        }

        $params = $botUser->getParameters();
        $params->addPath($route);

        $botUser->setParameters($params);
        $this->adminBotUserRepository->save($botUser);
    }

    private function resolveBotUser(BotUserInterface $botUser): ?AdminBotUser
    {
        return $this->adminBotUserRepository->findOneByUserId((int) $botUser->getUserId());
    }
}
