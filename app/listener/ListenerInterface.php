<?php

namespace app\listener;

interface ListenerInterface
{
    /**
     * @param Object $event
     * @return mixed
     */
    public function process(object $event);

}