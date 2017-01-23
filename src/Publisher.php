<?php

namespace Vinelab\Bowler;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class Publisher extends Producer
{
    /**
     * Part of the out-of-the-box Pub/Sub implementation
     * Set the default exchange named `pub-sub` of type `direct`.
     *
     * @param Vinelab\Bowler\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);

        $this->setup('pub-sub', 'direct');
    }

    /**
     *  Specify the Message routingKey
     *
     * @param string    $routingKey
     */
    public function setRoutingKey($routingKey)
    {
        $this->routingKey = $routingKey;
    }
}
