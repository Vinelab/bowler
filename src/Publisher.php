<?php

namespace Vinelab\Bowler;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class Publisher extends Producer
{
    /**
     * Part of the out-of-the-box Pub/Sub implementation
     * Set the default exchange named `pub-sub` of type `topic`.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);

        $this->setup('pub-sub', 'topic');
    }

    /**
     * Publish a message to the default Pub/Sub exchange.
     *
     * @param  string  $routingKey  The routing key used by the exchange to route messages to bounded queues
     * @param  string  $data
     */
    public function publish($routingKey, $data = null)
    {
        $this->send($data, $routingKey);
    }
}
