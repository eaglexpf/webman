<?php

namespace app\listener;

use DI\Annotation\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @Inject
     * @var ListenerProvider
     */
    protected $listeners;

    public function dispatch(object $event)
    {
        // TODO: Implement dispatch() method.
        foreach ($this->listeners->getListenersForEvent($event) as $listener) {
            $listener = make($listener);
            $listener->process($event);
            $this->dump($listener, $event);
            if ($listener instanceof StoppableEventInterface && $listener->isPropagationStopped()) {
                break;
            }
        }
    }

    private function dump($listener, object $event)
    {
        $eventName = get_class($event);
        $listenerName = '[ERROR TYPE]';
        if (is_array($listener)) {
            $listenerName = is_string($listener[0]) ? $listener[0] : get_class($listener[0]);
        } elseif (is_string($listener)) {
            $listenerName = $listener;
        } elseif (is_object($listener)) {
            $listenerName = get_class($listener);
        }
        logger()->debug(sprintf('Event %s handled by %s listener.', $eventName, $listenerName));
    }
}