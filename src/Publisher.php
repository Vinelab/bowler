<?php

namespace Vinelab\Bowler;

use Vinelab\Bowler\Producer;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class Publisher extends Producer
{
    /**
     * Part of the out-of-the-box Pub/Sub implementation
     * Default exchange name is `pub-sub` of type `direct`
     *
     * @param Vinelab\Bowler\Connection $connection
     * @param string                    $routingKey
     */
    public function __construct(Connection $connection, $routingKey)
    {
        parent::construct($connection, 'pub-sub', 'direct', $routingKey)
    }
}
