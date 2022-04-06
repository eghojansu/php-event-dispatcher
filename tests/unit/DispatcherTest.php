<?php

use Ekok\Container\Di;
use Ekok\EventDispatcher\Dispatcher;
use Ekok\EventDispatcher\Event;

class DispatcherTest extends \Codeception\Test\Unit
{
    /** @var Dispatcher */
    private $dispatcher;

    public function _before()
    {
        $this->dispatcher = new Dispatcher();
        $this->dispatcher->setContainer(new Di());
    }

    public function testDispatcher()
    {
        $this->dispatcher->on('foo', static function (Event $event) {
            $event->data[] = 1;
        });
        $this->dispatcher->one('foo', static function (Event $event) {
            $event->data[] = 2;
        });
        $this->dispatcher->on('foo', static function (Event $event) {
            $event->data[] = 3;
            $event->stopPropagation();
        });
        $this->dispatcher->on('foo', static function (Event $event) {
            $event->data[] = 4;
        });
        $this->dispatcher->on('foo', static function (Event $event) {
            $event->data[] = 5;
        }, 99);

        // first call
        $this->dispatcher->dispatch($event = Event::named('foo'));
        $this->assertSame(array(5, 1, 2, 3), $event->data);

        // second call
        $this->dispatcher->dispatch($event = new Event(), 'foo', true);
        $this->assertSame(array(5, 1, 3), $event->data);

        // third call
        $this->dispatcher->dispatch($event = Event::named('foo'));
        $this->assertObjectNotHasAttribute('data', $event);
    }

    public function testSubscriber()
    {
        $this->dispatcher->addSubscribers(array(FooSubscriber::class));

        // first call
        $this->dispatcher->dispatch($event = Event::named('event_foo'));
        $this->assertSame(array('second', 'first', 'third'), $event->data);

        // second call
        $this->dispatcher->dispatch($event = Event::named('event_foo'));
        $this->assertSame(array('second', 'first'), $event->data);

        // eventSameAsMethod
        $this->dispatcher->dispatch($event = Event::named('eventSameAsMethod'));
        $this->assertSame(array('eventSameAsMethod'), $event->data);

        // event_bar
        $this->dispatcher->dispatch($event = Event::named('event_bar'));
        $this->assertSame(array('method_bar'), $event->data);

        // eventSameAsMethod2
        $this->dispatcher->dispatch($event = Event::named('eventSameAsMethod2'));
        $this->assertSame(array('eventSameAsMethod2'), $event->data);
    }

    public function testSubscriberException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Subscriber stdClass should implements Ekok\\EventDispatcher\\EventSubscriberInterface');

        $this->dispatcher->addSubscriber(stdClass::class);
    }

    public function testAddListeners()
    {
        $called = false;

        $this->dispatcher->addListeners(array(
            'foo' => function() use (&$called) {
                $called = true;
            },
        ));
        $this->dispatcher->dispatch(Event::named('foo'));

        $this->assertTrue($called);
    }

    public function testFullName()
    {
        $this->dispatcher->on(Event::class, fn(Event $event) => $event->stopPropagation());
        $this->dispatcher->dispatch($event = new Event());

        $this->assertTrue($event->isPropagationStopped());
    }

    public function testEventName()
    {
        $this->dispatcher->on(MyEvent::class, static fn(MyEvent $event) => $event->from_class = 'foo');
        $this->dispatcher->on('my_event_name', static fn(MyEvent $event) => $event->from_name = 'bar');

        $this->dispatcher->dispatch($event = new MyEvent());
        $this->assertSame('bar', $event->from_name);
        $this->assertSame('foo', $event->from_class);
    }

    public function testRemoveEvent()
    {
        $this->dispatcher->on('foo', static fn(Event $event) => $event->foo = 'bar');
        $this->dispatcher->on('foo', static fn(Event $event) => $event->bar = 'baz');

        $this->dispatcher->dispatch($event = Event::named('foo'));
        $this->assertSame('bar', $event->foo);
        $this->assertSame('baz', $event->bar);

        $this->dispatcher->off('foo', 2);

        $this->dispatcher->dispatch($event = Event::named('foo'));
        $this->assertSame('bar', $event->foo);
        $this->assertObjectNotHasAttribute('bar', $event);

        $this->dispatcher->off('foo');

        $this->dispatcher->dispatch($event = Event::named('foo'));
        $this->assertObjectNotHasAttribute('foo', $event);
        $this->assertObjectNotHasAttribute('bar', $event);
    }
}
