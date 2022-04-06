<?php

declare(strict_types=1);

namespace Ekok\EventDispatcher;

use Ekok\Container\Di;
use Ekok\EventDispatcher\Attribute\Subscribe as AttributeSubscribe;
use Ekok\Utils\Call;
use Ekok\Utils\File;
use Ekok\Utils\Str;

class Dispatcher
{
    /** @var array */
    private $handlers = array();

    public function __construct(private Di $di)
    {}

    public function dispatch(Event $event, string $eventName = null, bool $once = false): static
    {
        $handlers = $this->getHandlers(get_class($event), $eventName ?? $event->getName());

        array_walk($handlers, function (Handler $handler, string $key) use ($event, $once) {
            if ($once || $handler->isOnce()) {
                unset($this->handlers[$key]);
            }

            $event->isPropagationStopped() || $this->di->call($handler->getCallable(), $event);
        });

        return $this;
    }

    public function loadClass(string|object $class): static
    {
        if (is_string($class) && is_subclass_of($class, EventSubscriberInterface::class)) {
            return $this->addSubscriber($class);
        }

        $ref = new \ReflectionClass($class);

        if (!$ref->isInstantiable()) {
            return $this;
        }

        $attrs = $ref->getAttributes(AttributeSubscribe::class);

        /** @var AttributeSubscribe|null */
        $attr = $attrs ? $attrs[0]->newInstance() : null;
        $listens = $attr?->listens ?? array();

        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $handler = Call::standarize($class, $method->name);

            if (!$attrs = $method->getAttributes(AttributeSubscribe::class)) {
                if (Str::equals($method->name, ...$listens)) {
                    $this->on($method->name, $handler);
                }

                continue;
            }

            /** @var AttributeSubscribe */
            $attr = $attrs[0]->newInstance();
            $registers = $attr->listens ?? array($method->name);

            array_walk($registers, fn (string $event) => $this->on($event, $handler));
        }

        return $this;
    }

    public function load(string $directory): static
    {
        $classes = File::getClassByScan($directory);

        array_walk($classes, fn (string $class) => $this->loadClass($class));

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

        $subscribes = $subscriber::getSubscribedEvents();

        array_walk($subscribes, fn($subscribe, $event) => $this->processSubscribe(
            $subscriber,
            $event,
            $subscribe,
        ));

        return $this;
    }

    public function addListeners(array $listeners): static
    {
        array_walk($listeners, fn($args, string $eventName) => $this->on($eventName, ...((array) $args)));

        return $this;
    }

    public function on(
        string $eventName,
        callable|string $handler,
        int $priority = null,
        bool $once = false,
        int $id = null,
    ): static {
        $wid = $id ?? $this->getNextHandlerId($eventName);
        $key = "$eventName.$wid";

        $this->handlers[$key] = new Handler(
            $eventName,
            $handler,
            $priority,
            $once,
            $wid,
        );

        uasort(
            $this->handlers,
            static fn (Handler $a, Handler $b) => $b->getPriority() <=> $a->getPriority(),
        );

        return $this;
    }

    public function one(string $eventName, callable|string $handler, int $priority = null): static
    {
        return $this->on($eventName, $handler, $priority, true);
    }

    public function off(string $eventName, int $id = null): static
    {
        $handlers = $this->getHandlers($eventName, null, $id);

        array_walk($handlers, function(...$args) {
            unset($this->handlers[$args[1]]);
        });

        return $this;
    }

    private function getNextHandlerId(string $eventName): int
    {
        $last = 0;
        $founds = $this->getHandlers($eventName);

        if ($founds) {
            uasort($founds, static fn (Handler $a, Handler $b) => $b->getId() <=> $a->getId());

            $last = reset($founds)->getId();
        };

        return $last + 1;
    }

    private function getHandlers(string $fbName, string|null $altName = null, int $id = null): array
    {
        return array_filter(
            $this->handlers,
            static fn (Handler $handler) => $handler->match($id, $fbName, $altName),
        );
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
