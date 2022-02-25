<?php

use Ekok\EventDispatcher\Event;
use Ekok\EventDispatcher\EventSubscriberInterface;

class FooSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return array(
            'event_foo' => array(
                array('first'),
                array('second', 99),
                array('third', null, true),
            ),
            'eventSameAsMethod',
            'event_bar' => 'method_bar',
            'eventSameAsMethod2' => null,
        );
    }

    public function first(Event $event)
    {
        $event->data[] = __FUNCTION__;
    }

    public function second(Event $event)
    {
        $event->data[] = __FUNCTION__;
    }

    public function third(Event $event)
    {
        $event->data[] = __FUNCTION__;
    }

    public function method_bar(Event $event)
    {
        $event->data[] = __FUNCTION__;
    }

    public function eventSameAsMethod(Event $event)
    {
        $event->data[] = __FUNCTION__;
    }

    public function eventSameAsMethod2(Event $event)
    {
        $event->data[] = __FUNCTION__;
    }
}
