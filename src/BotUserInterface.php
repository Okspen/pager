<?php

declare(strict_types=1);

namespace Pager;

/**
 * Interface BotUserInterface
 * @package Pager
 */
interface BotUserInterface
{
    public function getUserId(): string;

    public function getFirstName(): ?string;

    public function getLastName(): ?string;

    public function resetPath(): void;

    public function addPath(string $path): void;

    public function back(): void;

    public function last(): ?string;

    public function lastScreen(): ?string;
}
