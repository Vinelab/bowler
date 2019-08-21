<?php

namespace Vinelab\Bowler;

use Closure;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Logging\Log;
use PhpAmqpLib\Message\AMQPMessage;
use Vinelab\Bowler\Exceptions\UnrecalledAMQPMessageException;

class MessageLifecycleManager
{
    /**
     * @var Log
     */
    protected $logger;

    /**
     * @var Repository
     */
    protected $config;

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
     * MessageLifecycleManager constructor.
     * @param  Log  $logger
     * @param  Repository  $config
     */
    public function __construct(Log $logger, Repository $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

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
            $msg = $this->executeCallback($msg, $callback, func_get_args());

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
            $this->executeCallback($msg, $callback, func_get_args());
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
            $msg = $this->executeCallback($msg, $callback, func_get_args());

            if (!$msg instanceof AMQPMessage) {
                throw new UnrecalledAMQPMessageException('Callback must return instance of AMQPMessage');
            }
        }

        return $msg;
    }

    /**
     * @param  AMQPMessage  $msg
     * @param  string  $queueName`
     * @param  string  $handlerClass
     * @param  Ack  $ack
     * @return void
     */
    public function triggerConsumed(AMQPMessage $msg, string $queueName, string $handlerClass, Ack $ack)
    {
        foreach ($this->consumed as $callback) {
            $this->executeCallback($msg, $callback, func_get_args());
        }
    }

    /**
     * @param  AMQPMessage  $msg
     * @param  Closure  $callback
     * @param  array  $args
     * @return mixed
     * @throws Exception
     */
    protected function executeCallback(AMQPMessage $msg, Closure $callback, array $args)
    {
        try {
            $msg = call_user_func_array($callback, $args);
        } catch (Exception $e) {
            $this->handleCallbackError($e);
        }

        return $msg;
    }

    /**
     * @param  Exception  $e
     * @throws Exception
     */
    protected function handleCallbackError(Exception $e)
    {
        if ($this->config->get('bowler.lifecycle_hooks.fail_on_error')) {
            throw $e;
        } else {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }
    }
}
