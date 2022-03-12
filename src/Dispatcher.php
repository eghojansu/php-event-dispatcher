<?php

declare(strict_types=1);

namespace Ekok\EventDispatcher;

use Ekok\Utils\Arr;
use Ekok\Utils\Str;
use Ekok\Container\Di;

class Dispatcher
{
    private $events = array();
    private $sorted = array();

    public function __construct(private Di $di)
    {}

    public function dispatch(Event $event, string $eventName = null, bool $once = false): static
    {
        $name = $eventName ?? $event->getName() ?? Str::className(get_class($event), true);
        $handlers = $this->getHandlers($name);

        if ($once) {
            $this->off($name);
        }

        Arr::some(
            $handlers,
            function (Handler $handler) use ($event, $name) {
                $this->di->call($handler->getHandler(), $event);

                if ($handler->isOnce()) {
                    $this->off($name, $handler->getPosition());
                }

                return $event->isPropagationStopped();
            },
        );

        return $this;
    }

    public function addSubscribers(array $subscribers): static
    {
        array_walk($subscribers, fn($subscriber) => $this->addSubscriber($subscriber));

        return $this;
    }

    public function addSubscriber(EventSubscriberInterface|string $subscriber): static
    {
        if (is_string($subscriber) && !is_subclass_of($subscriber, EventSubscriberInterface::class)) {
            throw new \LogicException(sprintf(
                'Subscriber %s should implements %s',
                $subscriber,
                EventSubscriberInterface::class,
            ));
        }

        $subscribes = array($subscriber, 'getSubscribedEvents');

        Arr::each(
            $subscribes(),
            fn($subscribe, $event) => $this->processSubscribe(
                $subscriber,
                $event,
                $subscribe,
            ),
        );

        return $this;
    }

    public function addListeners(array $listeners): static
    {
        array_walk($listeners, fn($args, string $eventName) => $this->on($eventName, ...((array) $args)));

        return $this;
    }

    public function on(string $eventName, callable|string $handler, int $priority = null, bool $once = false): static
    {
        $name = strtolower($eventName);
        $events = &$this->events[$name];

        $events[] = new Handler($handler, $priority, $once, count($events ?? array()));

        $this->sorted[$name] = null;

        return $this;
    }

    public function one(string $eventName, callable|string $handler, int $priority = null): static
    {
        return $this->on($eventName, $handler, $priority, true);
    }

    public function off(string $eventName, int $pos = null): static
    {
        $name = strtolower($eventName);

        if (null === $pos) {
            unset($this->events[$name]);
        } else {
            unset($this->events[$name][$pos]);
            array_walk($this->events[$name], fn(Handler $handler, $pos) => $handler->setPosition($pos));
        }

        unset($this->sorted[$name]);

        return $this;
    }

    private function getHandlers(string $eventName): array
    {
        $name = strtolower($eventName);
        $sorted = &$this->sorted[$name];

        if (null === $sorted && isset($this->events[$name])) {
            $sorted = $this->events[$name];

            usort($sorted, static fn (Handler $a, Handler $b) => $b->getPriority() <=> $a->getPriority());
        }

        return $sorted ?? array();
    }

    private function processSubscribe(EventSubscriberInterface|string $subscriber, string|int $event, array|string|null $subscribe): void
    {
        $arguments = array();
        $method = $subscribe ?? $event;

        if (is_array($method)) {
            if (is_array($method[0] ?? null)) {
                array_walk($method, fn($subscribe) => $this->processSubscribe($subscriber, $event, $subscribe));

                return;
            }

            $arguments = $method;
            $method = array_shift($arguments);
        }

        $name = is_numeric($event) ? $method : $event;
        $handler = is_string($subscriber) ? $subscriber . '@' . $method : array($subscriber, $method);

        $this->on($name, $handler, ...$arguments);
    }
}
