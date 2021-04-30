<?php

namespace app\listener;

class ListenerData
{
    public $event;
    public $listener;
    public $priority;

    public function __construct($event, $listener, $priority)
    {
        $this->event    = $event;
        $this->listener = $listener;
        $this->priority = $priority;
    }

}