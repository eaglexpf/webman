<?php

namespace app\console;

use app\commands\DemoCommand;

class Kernel
{
    public $commands = [
        DemoCommand::class,
    ];

}