<?php

namespace Ekok\EventDispatcher;

class Handler
{
    /** @var string|callable */
    public $handler;

    /** @var int|null */
    public $position;

    /** @var int */
    public $priority = 0;

    /** @var bool */
    public $once = false;

    public function __construct(
        callable|string $handler,
        int $priority = null,
        bool $once = null,
        int $position = null,
    ) {
        $this->handler = $handler;
        $this->priority = $priority ?? 0;
        $this->once = $once ?? false;
        $this->position = $position;
    }

    public function getPosition(): int|null
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }
}
