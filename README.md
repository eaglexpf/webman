# webman
基于 [webman](https://github.com/walkor/webman) 的个人项目骨架

## 新增内容
1. 基于`illuminate/events`的事件处理
2. 基于`symfony/console`的命令行处理
3. 基于`RABBITMQ`的队列处理


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

- rabbitmq队列
```
sudo vim src/DemoBundle/Jobs/DemoJob.php
<<<
<?php

namespace DemoBundle\Jobs;

use app\jobs\Job;

class DemoJob extends Job
{
    protected $data;

    /**
     * TestJob constructor.
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        var_dump($this->data);

        return true;
    }

}
>>>

sudo vim src/DemoBundle/Commands/DemoCommand.php

<<<
<?php

namespace DemoBundle\Commands;

use app\commands\BaseCommand;
use DemoBundle\Jobs\DemoJob;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class DemoCommand extends BaseCommand
{
    public function configure()
    {
        $this->setName('queue:demo')
            ->setDescription('队列任务 queue:demo [default: 发送的消息] [0: 延迟时间]')
            ->addArgument('msg', InputOption::VALUE_REQUIRED, '发送的消息', 'hello world!')
            ->addArgument('ttl', InputOption::VALUE_REQUIRED, '延迟时间', 0);
    }

    public function handle(InputInterface $input)
    {
        $msg = $input->getArgument('msg');
        $ttl = $input->getArgument('ttl');
        sendJob(new DemoJob($msg))->setQueue('webman')->setTtl($ttl)->send();
        return true;
    }

}
>>>


# 消费队列
php command queue:work [default:队列名称] [default:连接类型]

# 生成队列
php command queue:demo [msg:生产消息] [0:延迟时间]

```