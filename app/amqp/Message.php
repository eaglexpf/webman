<?php
namespace app\amqp;

class Message
{
    protected $poolName = 'default';
    protected $type = '';
    protected $exchange = 'default';
    protected $queue = 'default';
    protected $routingKey = 'default';
    protected $ttl = 0;

    protected $payload;
    protected $properties;

    protected $timeout = 5;

    /**
     * Message constructor.
     * @param object $job
     */
    public function __construct(object $job)
    {
        if (!method_exists($job, 'handle')) {
            throw new \Exception('job类必须包含handle方法');
        }
        $this->payload = [
            'displayName' => get_class($job),
            'command' => serialize(clone $job),
            'maxTries' => isset($job->tries) ? $job->tries : null,
            'timeout' => isset($job->timeout) ? $job->timeout : null,
        ];
    }

    /**
     * @return string
     */
    public function getPoolName(): string
    {
        return $this->poolName;
    }

    /**
     * @param string $poolName
     * @return $this
     */
    public function setPoolName(string $poolName): self
    {
        $this->poolName = $poolName;
        return $this;
    }



    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getExchange(): string
    {
        return $this->exchange;
    }

    /**
     * @param string $exchange
     * @return $this
     */
    public function setExchange(string $exchange): self
    {
        $this->exchange = $exchange;
        return $this;
    }

    /**
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * @param string $queue
     * @return $this
     */
    public function setQueue(string $queue): self
    {
        $this->queue = $queue;
        $this->setRoutingKey($queue);
        return $this;
    }

    /**
     * @return string
     */
    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }

    /**
     * @param string $routingKey
     * @return $this
     */
    public function setRoutingKey(string $routingKey): self
    {
        $this->routingKey = $routingKey;
        return $this;
    }

    /**
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * @param int $ttl
     * @return $this
     */
    public function setTtl(int $ttl): self
    {
        $this->ttl = $ttl;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param mixed $payload
     * @return $this
     */
    public function setPayload($payload): self
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param mixed $properties
     * @return $this
     */
    public function setProperties($properties): self
    {
        $this->properties = $properties;
        return $this;
    }


    /**
     * Serialize the message body to a string.
     */
    public function serialize(): string
    {
        return json_encode($this->payload);
    }

    /**
     * Unserialize the message body.
     */
    public function unserialize(string $data)
    {
        $this->payload = @json_decode($data, true);
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     * @return $this
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function send()
    {
        $producer = make(\app\amqp\Producer::class);
        return $producer->send($this);
    }

}