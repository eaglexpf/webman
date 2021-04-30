<?php

namespace app\amqp;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;

class Connection
{
    protected static $_instance = [];
    protected static $_bind = [];

    public static function getConnection($name): AMQPStreamConnection
    {
        $config = config('amqp');
        $config = $config[$name] ?? [];
        if (empty($config)) {
            throw new \Exception('amqp配置读取失败:' . $name);
        }
        if (isset(self::$_instance[$name])) {
            return self::$_instance[$name];
        }
        $connection = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['password']);
        self::$_instance[$name] = $connection;
        return $connection;
    }

    public static function bindProducer(AMQPChannel $channel, Message &$msg)
    {
        $delayExchange   = $msg->getExchange();
        $delayQueue      = $msg->getQueue() . '_deferred_' . $msg->getTtl();
        $delayRoutingKey = $delayQueue;
        $md5 = md5($msg->getExchange() . $msg->getQueue() . $msg->getRoutingKey() . $msg->getTtl());
        if (isset(self::$_bind[$md5])) {
            if ($msg->getTtl() > 0) {
                $msg->setExchange($delayExchange);
                $msg->setRoutingKey($delayRoutingKey);
            }
            return true;
        }
        // 交换器类型
        // direct: (默认)直接交换器，工作方式类似于单播，Exchange会将消息发送完全匹配ROUTING_KEY的Queue,
        // fanout: 广播是式交换器，不管消息的ROUTING_KEY设置为什么，Exchange都会将消息转发给所有绑定的Queue,
        // topic:  主题交换器，工作方式类似于组播，Exchange会将消息转发和ROUTING_KEY匹配模式相同的所有队列，比如，ROUTING_KEY为user.stock的Message会转发给绑定匹配模式为 * .stock,user.stock， * . * 和#.user.stock.#的队列。(* 表是匹配一个任意词组，#表示匹配0个或多个词组),
        // headers:根据消息体的header匹配
        $exchange_type = env('RABBITMQ_EXCHANGE_TYPE', 'direct');
        // 是否检测同名交换器
        $exchange_passive = env('RABBITMQ_EXCHANGE_PASSIVE', false);
        // 是否持久化交换器
        $exchange_durable = env('RABBITMQ_EXCHANGE_DURABLE', true);
        // 是否自动删除交交换器  当所有与此交换器绑定的队列与此交换器解绑 此交换器自动删除
        $exchange_auto_delete = env('RABBITMQ_EXCHANGE_PASSIVE', false);

        // 是否检测同名队列
        $queue_passive = env('RABBITMQ_QUEUE_PASSIVE', false);
        // 是否持久化
        $queue_durable = env('RABBITMQ_QUEUE_DURABLE', true);
        // 是否独占，为true则设置为独占队列，独占该队列仅对首次声明它的连接可见，并在连接断开时自动删除
        $queue_exclusive = env('RABBITMQ_QUEUE_EXCLUSIVE', false);
        // 是否自动删除，至少有一个消费者连接到这个队列，之后所有与这个队列连接的消费者都断开时，才会删除
        $queue_auto_delete = env('RABBITMQ_QUEUE_AUTO_DELETE', false);

        //定义交换器
        $channel->exchange_declare($msg->getExchange(), $exchange_type, $exchange_passive, $exchange_durable, $exchange_auto_delete);
        //定义队列
        $channel->queue_declare($msg->getQueue(), $queue_passive, $queue_durable, $queue_exclusive, $queue_auto_delete);
        //绑定队列到交换器上
        $channel->queue_bind($msg->getQueue(), $msg->getExchange(), $msg->getRoutingKey());
        if ($msg->getTtl() > 0) {
            //定义延迟交换器
            $channel->exchange_declare($delayExchange, $exchange_type, $exchange_passive, $exchange_durable, $exchange_auto_delete);

            //定义延迟队列
            $channel->queue_declare($delayQueue, $queue_passive, $queue_durable, $queue_exclusive, $queue_auto_delete, false, new AMQPTable(array(
                "x-dead-letter-exchange"    => $msg->getExchange(),
                "x-dead-letter-routing-key" => $msg->getRoutingKey(),
                "x-message-ttl"             => $msg->getTtl() * 1000,
            )));
            //绑定延迟队列到交换器上
            $channel->queue_bind($delayQueue, $delayExchange, $delayRoutingKey);

            $msg->setExchange($delayExchange);
            $msg->setRoutingKey($delayRoutingKey);
        }
        self::$_bind[$md5] = true;
    }

    protected static $_bind_consumer = [];

    public static function bindConsumer(AMQPChannel $channel, string $queue)
    {
        $md5 = md5($queue);
        if (isset(self::$_bind_consumer[$md5])) {
            return true;
        }
        // 是否检测同名队列
        $queue_passive = env('RABBITMQ_QUEUE_PASSIVE', false);
        // 是否持久化
        $queue_durable = env('RABBITMQ_QUEUE_DURABLE', true);
        // 是否独占，为true则设置为独占队列，独占该队列仅对首次声明它的连接可见，并在连接断开时自动删除
        $queue_exclusive = env('RABBITMQ_QUEUE_EXCLUSIVE', false);
        // 是否自动删除，至少有一个消费者连接到这个队列，之后所有与这个队列连接的消费者都断开时，才会删除
        $queue_auto_delete = env('RABBITMQ_QUEUE_AUTO_DELETE', false);
        $channel->queue_declare($queue, $queue_passive, $queue_durable, $queue_exclusive, $queue_auto_delete);

        self::$_bind_consumer[$md5] = true;
    }
}