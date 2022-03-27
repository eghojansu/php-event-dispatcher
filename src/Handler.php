<?php

declare(strict_types=1);

namespace Ekok\EventDispatcher;

class Handler
{
    public function __construct(
        private string $eventName,
        private $callable,
        private int|null $priority,
        private bool|null $once,
        private int $id,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function getCallable(): string|callable
    {
        return $this->callable;
    }

    public function isOnce(): bool
    {
        return $this->once ?? false;
    }

    public function getPriority(): int
    {
        return $this->priority ?? 0;
    }

    public function match(int|null $id, string|null ...$names): bool
    {
        $check = $this->getEventName();

        return (null === $id || $id === $this->id) && array_reduce($names, fn($match, $name) => $match || (
            $name && ($name === $check || 0 === strcasecmp($name, $check))
        ), false);
    }
}
