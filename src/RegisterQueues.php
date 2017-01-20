<?php

namespace Vinelab\Bowler;

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
     * Registrator::subscribe
     * Default out-of-box Publisher/Subscriber setup.
     *
     * @param string $queue
     * @param string $className
     * @param array  $bindingKeys
     */
    public function subscribe($queue, $className, array $bindingKeys)
    {
        // Default pub/sub setup
        // We only need the bindingKeys to be enable key based pub/sub
        $options = array_filter([
                        'exchangeName' => 'pub-sub',
                        'exchangeType' => 'direct',
                        'bindingKeys' => $bindingKeys,
                        'passive' => false,
                        'durable' => true,
                        'autoDelete' => false,
                        'deliveryMode' => 2,
                    ]);

        $this->queue($queue, $className, $options);
    }

    public function getHandlers()
    {
        return $this->handlers;
    }
}
