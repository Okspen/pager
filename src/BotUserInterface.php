<?php

declare(strict_types=1);

namespace Pager\Service\Bot;

use Pager\Entity\Dto\BotUserParameters;

interface BotUserInterface
{
    public function getUserId(): string;

    public function getFirstName(): ?string;

    public function getLastName(): ?string;

    public function setParameters(BotUserParameters $parameters): void;

    public function getParameters(): BotUserParameters;
}
