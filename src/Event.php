<?php

declare(strict_types=1);

namespace Ekok\EventDispatcher;

class Event
{
    private $name;
    private $propagationStopped = false;

    public static function createDefault(): static
    {
        return new static();
    }

    public static function named(string $name): static
    {
        $self = self::createDefault();
        $self->name = $name;

        return $self;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): static
    {
        $this->propagationStopped = true;

        return $this;
    }
}
