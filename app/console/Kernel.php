<?php

namespace app\console;

use app\commands\QueueCommand;
use DemoBundle\Commands\DemoCommand;

class Kernel
{
    public $commands = [
        QueueCommand::class,
        DemoCommand::class,
    ];

}