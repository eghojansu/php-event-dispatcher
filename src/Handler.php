<?php

namespace Ekok\EventDispatcher;

class Handler
{
    /** @var string|callable */
    private $handler;

    /** @var int|null */
    private $position;

    /** @var int */
    private $priority = 0;

    /** @var bool */
    private $once = false;

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

    public function getHandler(): string|callable
    {
        return $this->handler;
    }

    public function isOnce(): bool
    {
        return $this->once;
    }

    public function getPriority(): int
    {
        return $this->priority;
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
