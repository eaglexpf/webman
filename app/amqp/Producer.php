<?php
namespace app\amqp;

use PhpAmqpLib\Message\AMQPMessage;

class Producer
{
    public function send(Message $msg)
    {
        return retry(1, function () use ($msg) {
           return $this->produce($msg);
        });
    }

    protected function produce(Message $msg)
    {
        $message = new AMQPMessage($msg->serialize(), $msg->getProperties());
        $connection = Connection::getConnection($msg->getPoolName());
        $channel = $connection->channel();
        $channel->set_ack_handler(function () use (&$result) {
            $result = true;
        });
        try {
            // 检测交换机和队列
            Connection::bindProducer($channel, $msg);

            $channel->basic_publish($message, $msg->getExchange(), $msg->getRoutingKey());
            $channel->wait_for_pending_acks_returns($msg->getTimeout());
        } catch (\Throwable $exception) {
            // Reconnect the connection before release.
            $connection->reconnect();
            throw $exception;
        }

        return true;
    }

}