<?php

declare(strict_types=1);

namespace Pager\Dto;

class CallbackData
{
    /** @var string */
    public $id;

    /** @var string */
    private $data;

    /** @var string */
    public $commandName;

    /** @var array */
    public $arguments = [];

    public function __construct(string $id, string $data)
    {
        $this->id = $id;
        $this->data = $data;
        $this->init();
    }

    private function init(): void
    {
        /** @var string $cleanData */
        $cleanData = trim($this->data, '/');

        /** @var array $cleanDataParts */
        $cleanDataParts = explode(' ', $cleanData);

        $this->commandName = $cleanDataParts[0];
        if (\count($cleanDataParts) > 0) {
            $this->arguments = array_splice($cleanDataParts, 1, \count($cleanDataParts) - 1);
        }
    }
}
