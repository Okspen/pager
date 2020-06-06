<?php

declare(strict_types=1);

namespace Pager\Screen;

class RunResult
{
    /** @var string|null */
    public $next;

    /**
     * @var bool|null
     *
     * Whether to ignore or not the telegram update object
     */
    public $firstVisit;

    public static function ok(string $next = null, bool $firstVisit = null): self
    {
        $r = new self();
        $r->next = $next;
        $r->firstVisit = $firstVisit;

        return $r;
    }
}
