<?php

declare(strict_types=1);

namespace Ekok\EventDispatcher;

interface EventSubscriberInterface
{
    public static function getSubscribedEvents(): array;
}
