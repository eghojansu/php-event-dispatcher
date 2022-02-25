<?php

namespace Ekok\EventDispatcher;

class Event
{
    private $propagationStopped = false;

    public function __construct(private string|null $name = null)
    {}

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
