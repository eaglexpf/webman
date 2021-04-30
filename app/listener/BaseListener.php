<?php

namespace app\listener;

class BaseListener extends StoppableEvent implements ListenerInterface
{
    public function process(object $event)
    {
        // TODO: Implement process() method.
        throw new \Exception('The process method must exist!');
    }

}