<?php

namespace app\listener;

use Psr\EventDispatcher\ListenerProviderInterface;

class ListenerProvider implements ListenerProviderInterface
{
    protected $listeners = [];

    public function getListenersForEvent(object $event): iterable
    {
        $this->insertEventListener($event);
        $queue = new \SplPriorityQueue();
        // TODO: Implement getListenersForEvent() method.
        foreach ($this->listeners as $event_class_name => $listeners) {
            if (make($event_class_name) instanceof $event) {
                foreach ($listeners as $listener) {
                    $queue->insert($listener->listener, $listener->priority);
                }
            }
        }
        return $queue;
    }

    public function insertEventListener(object $event)
    {
        if (!empty($this->listeners[get_class($event)])) {
            return true;
        }
        $config = config('event');
        foreach ($config as $event_class_name => $listeners) {
            $listeners = array_reverse($listeners);
            $num = 0;
            foreach ($listeners as $key => $listener) {
                if (make($listener) instanceof BaseListener) {
                    $this->on($event_class_name, $listener, $key);
                    $num = $key;
                }
            }
            if (make($event_class_name) instanceof $event) {
                $listeners = array_reverse($event->listeners());
                foreach ($listeners as $key => $listener) {
                    if (make($listener) instanceof BaseListener) {
                        $this->on($event_class_name, $listener, $key + $num);
                    }
                }
            }
        }
    }

    public function on(string $event, string $listener, int $priority = 1): void
    {
        $this->listeners[$event][] = new ListenerData($event, $listener, $priority);
    }

}