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
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    /**
     * @param string $exchangeName
     * @param string $routingKey
     * @param string|null $data
     * @param string $exchangeType
     */
    public function dispatch($exchangeName, $routingKey, $data = null, $exchangeType = 'topic')
    {
        $this->setup($exchangeName, $exchangeType);
        $this->send($data, $routingKey);
    }
}
