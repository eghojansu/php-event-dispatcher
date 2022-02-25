<?php

namespace Ekok\EventDispatcher;

interface EventSubscriberInterface
{
    public static function getSubscribedEvents(): array;
}
