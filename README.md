# webman
基于 [webman](https://github.com/walkor/webman) 的个人项目骨架

## 新增内容
1. 基于`illuminate/events`的事件处理器
2. 基于`symfony/console`的命令行处理器


## demo
- 事件处理
```
mkdir -p src/DemoBundle/Events
vim src/DemoBundle/Events/DemoEvent.php

<<<
<?php
namespace DemoBundle\Events;

use DemoBundle\Listeners\TestListner;

class DemoEvent
{
    public function listeners(): array
    {
        return [
            TestListener::class,
        ];
    }

    protected $data;
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}

>>>

mkdir -p src/DemoBundle/Listeners
vim src/DemoBundle/Listeners/DemoListener.php
<<<
<?php
namespace DemoBundle\Listeners;

use app\listeners\BaseListener
use DemoBundle\Events\DemoEvent;

class DemoListener extend BaseListener
{
    public function process(object $event)
    {
        var_dump('demoListener',get_class($event));
    }

    /**
     * 是否阻断事件传递
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return false;
    }
}
>>>

mkdir -p src/DemoBundle/Listeners
vim src/DemoBundle/Listeners/TestListener.php
<<<
<?php
namespace DemoBundle\Listeners;

use app\listeners\BaseListener
use DemoBundle\Events\DemoEvent;

class TestListener extend BaseListener
{
    public function process(object $event)
    {
        var_dump('testListener',get_class($event));
    }
}
>>>

sudo vim config/event.php
<<<
<?php

return [
    DemoBundle\Events\DemoEvent::class => [
        DemoBundle\Listeners\DemoListener::class,
    ],
];
>>>
```

- 命令行
```
sudo vim src/DemoBundle/Commands/DemoCommand.php
<<<
<?php
namespace DemoBundle\Commands;

use app\commands\BaseCommand
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class DemoCommand extends BaseCommand
{

    public function configure()
    {
        $this->setName('demo:command')
            ->setDescription('命令描述 --type: [list: 列表]')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, '要执行的类型');
    }

    public function handle(InputInterface $input)
    {
        $this->io->success($input->getOption('type'));
        $this->io->success(date('Y-m-d H:i:s'));
        return true;
    }
}
>>>

```