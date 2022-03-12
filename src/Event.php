<?php

declare(strict_types=1);

namespace Ekok\EventDispatcher;

class Event
{
    private $name;
    private $propagationStopped = false;

    public function __construct(string $name = null)
    {
        $this->setName($name);
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

    protected function setName(string|null $name): static
    {
        $this->name = $name;

        return $this;
    }
}
