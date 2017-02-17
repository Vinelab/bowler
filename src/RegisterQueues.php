<?php

namespace Vinelab\Bowler;

use Vinelab\Bowler\Exceptions\InvalidSubscriberBindingException;

/**
 * @author Ali Issa <ali@vinelab.com>
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class RegisterQueues
{
    private $handlers = [];

    /**
     * Registrator::queue.
     *
     * @param string $queue
     * @param string $className
     * @param array  $options
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
     * @param string $queue
     * @param string $className
     * @param array  $bindingKeys
     */
    public function subscriber($queue, $className, array $bindingKeys)
    {
        if (empty($bindingKeys)) {
            throw new InvalidSubscriberBindingException('Missing bindingKeys for Subscriber queue: '.$queue.'.');
        }

        // Default pub/sub setup
        // We only need the bindingKeys to enable key based pub/sub
        $options = array_filter([
                        'exchangeName' => 'pub-sub',
                        'exchangeType' => 'topic',
                        'bindingKeys' => $bindingKeys,
                        'passive' => false,
                        'durable' => true,
                        'autoDelete' => false,
                    ]);

        $this->queue($queue, $className, $options);
    }

    public function getHandlers()
    {
        return $this->handlers;
    }
}
