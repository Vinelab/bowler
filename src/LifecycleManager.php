<?php

namespace Vinelab\Bowler;

use Closure;
use PhpAmqpLib\Message\AMQPMessage;
use Vinelab\Bowler\Exceptions\UnrecalledAMQPMessageException;

class LifecycleManager
{
    /**
     * @var array
     */
    protected $beforePublish = [];

    /**
     * @var array
     */
    protected $published = [];

    /**
     * @var array
     */
    protected $beforeConsume = [];

    /**
     * @var array
     */
    protected $consumed = [];

    /**
     * @param  Closure  $callback
     * @return void
     */
    public function beforePublish(Closure $callback)
    {
        $this->beforePublish[] = $callback;
    }

    /**
     * @param  Closure  $callback
     * @return void
     */
    public function published(Closure $callback)
    {
        $this->published[] = $callback;
    }

    /**
     * @param  Closure  $callback
     * @return void
     */
    public function beforeConsume(Closure $callback)
    {
        $this->beforeConsume[] = $callback;
    }

    /**
     * @param  Closure  $callback
     * @return void
     */
    public function consumed(Closure $callback)
    {
        $this->consumed[] = $callback;
    }

    /**
     * @param  AMQPMessage  $msg
     * @param  string  $exchangeName
     * @param  null  $routingKey
     * @return AMQPMessage
     * @throws UnrecalledAMQPMessageException
     */
    public function triggerBeforePublish(AMQPMessage $msg, string $exchangeName, $routingKey = null): AMQPMessage
    {
        foreach ($this->beforePublish as $callback) {
            $msg = $callback($msg, $exchangeName, $routingKey);

            if (!$msg instanceof AMQPMessage) {
                throw new UnrecalledAMQPMessageException('Callback must return instance of AMQPMessage');
            }
        }

        return $msg;
    }

    /**
     * @param  AMQPMessage  $msg
     * @param  string  $exchangeName
     * @param  null  $routingKey
     * @return void
     */
    public function triggerPublished(AMQPMessage $msg, string $exchangeName, $routingKey = null)
    {
        foreach ($this->published as $callback) {
            $callback($msg, $exchangeName, $routingKey);
        }
    }

    /**
     * @param  AMQPMessage  $msg
     * @param  string  $queueName
     * @param  string  $handlerClass
     * @return AMQPMessage
     * @throws UnrecalledAMQPMessageException
     */
    public function triggerBeforeConsume(AMQPMessage $msg, string $queueName, string $handlerClass): AMQPMessage
    {
        foreach ($this->beforeConsume as $callback) {
            $msg = $callback($msg, $queueName, $handlerClass);

            if (!$msg instanceof AMQPMessage) {
                throw new UnrecalledAMQPMessageException('Callback must return instance of AMQPMessage');
            }
        }

        return $msg;
    }

    /**
     * @param  AMQPMessage  $msg
     * @param  string  $queueName
     * @param  string  $handlerClass
     * @param  Ack  $ack
     * @return void
     */
    public function triggerConsumed(AMQPMessage $msg, string $queueName, string $handlerClass, Ack $ack)
    {
        foreach ($this->consumed as $callback) {
            $callback($msg, $queueName, $handlerClass, $ack);
        }
    }
}