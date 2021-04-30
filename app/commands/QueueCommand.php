<?php

namespace app\commands;

use app\amqp\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class QueueCommand extends BaseCommand
{

    public function configure()
    {
        $this->setName('queue:work')
            ->setDescription('队列任务 queue:work [default: 队列名称] [default: 连接类型]')
            ->addArgument('queue', InputOption::VALUE_REQUIRED, '队列名称', 'default')
            ->addArgument('connection', InputOption::VALUE_REQUIRED, '连接类型', 'default');
    }

    public function handle(InputInterface $input)
    {
        $this->receiver($this->io, $input->getArgument('queue'), $input->getArgument('connection'));
        return true;
    }

    public function receiver(SymfonyStyle $io, $queue = 'default', $config = 'default')
    {
        $io->info('QUEUE_WORK_START');
        $connection = Connection::getConnection($config);
        $channel = $connection->channel();
        Connection::bindConsumer($channel, $queue);
        $callback = function ($msg) use ($io) {
            $datetime = date('Y-m-d H:i:s') . ":\t";
            $job = json_decode($msg->body, true);
            if (empty($job['command'])) {
                $io->error($datetime . 'error job:' . $msg->body);
                return false;
            }
            $obj = unserialize($job['command']);
            if (!method_exists($obj, 'handle')) {
                return false;
            }
            $io->info($datetime . get_class($obj) . ':RUNNING');
            if ($obj->handle()) {
                $io->info($datetime . get_class($obj) . ':SUCCESS');
                return $msg->ack();
            }
            $io->info($datetime . get_class($obj) . ':FAIL');
            return false;
        };

        $channel->basic_consume($queue, '', false, false, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }
        $io->info('QUEUE_WORK_CLOSE');
    }
}