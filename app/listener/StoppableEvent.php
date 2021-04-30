<?php

namespace app\listener;

use Psr\EventDispatcher\StoppableEventInterface;

class StoppableEvent implements StoppableEventInterface
{
    protected $stoppable = false;
    public function isPropagationStopped(): bool
    {
        // TODO: Implement isPropagationStopped() method.
        return $this->stoppable;
    }

    /**
     * @param bool $stoppable
     * @return $this
     */
    public function setStoppable(bool $stoppable): self
    {
        $this->stoppable = $stoppable;
        return $this;
    }

}