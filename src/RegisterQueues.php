<?php

namespace Vinelab\Bowler;

use Vinelab\Bowler\Exceptions\InvalidSubscriberBindingException;

/**
 * @author Ali Issa <ali@vinelab.com>
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class RegisterQueues
{
    /**
     * @var array
     */
    private $handlers = [];

    /**
     * Registrator::queue.
     *
     * @param  string  $queue
     * @param  string  $className
     * @param  array  $options
     */
    public function queue($queue, $className, $options = [])
    {
        $handler = new Handler();
        $handler->queueName = $queue;
        $handler->className = $className;
        $handler->options = $options;

        array_push($this->handlers, $handler);
    }

    /**
     * Registrator::subscriber.
     * Default out-of-box Publisher/Subscriber setup.
     *
     * @param  string  $queue
     * @param  string  $className
     * @param  array  $bindingKeys
     * @param  string  $exchangeName
     * @param  string  $exchangeType
     * @param  array  $options
     * @throws InvalidSubscriberBindingException
     */
    public function subscriber(string $queue, string $className, array $bindingKeys, string $exchangeName = 'pub-sub', string $exchangeType = 'topic', array $options = [])
    {
        if (empty($bindingKeys)) {
            throw new InvalidSubscriberBindingException('Missing bindingKeys for Subscriber queue: ' . $queue . '.');
        }

        // Default pub/sub setup
        // We only need the bindingKeys to enable key based pub/sub
        $defaultOptions = [
            'exchangeName' => $exchangeName,
            'exchangeType' => $exchangeType,
            'bindingKeys' => $bindingKeys,
            'passive' => false,
            'durable' => true,
            'autoDelete' => false,
        ];

        // Append to and/or override default options
        $options = array_merge($defaultOptions, $options);

        $this->queue($queue, $className, $options);
    }

    /**
     * @return array
     */
    public function getHandlers()
    {
        return $this->handlers;
    }
}
