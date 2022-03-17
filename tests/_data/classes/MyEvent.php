<?php

use Ekok\EventDispatcher\Event;

class MyEvent extends Event
{
    public function getName(): ?string
    {
        return 'my_event_name';
    }
}
