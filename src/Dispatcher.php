<?php

namespace Vinelab\Bowler;

/**
 * @author Abed Halawi <abed.halawi@vinelab.com>
 */
class Dispatcher extends Producer
{
    /**
     * Part of the fair dispatch implementation
     * Allow setting the exchange name and type with default of `topic`.
     *
     * @param Vinelab\Bowler\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    public function dispatch($exchangeName, $routingKey, $data = null, $exchangeType = 'topic')
    {
        $this->setup($exchangeName, $exchangeType);
        $this->send($data, $routingKey);
    }
}
