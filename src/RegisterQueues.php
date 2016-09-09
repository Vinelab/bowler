<?php

namespace Vinelab\Bowler;

use Vinelab\Bowler\Handler;

class RegisterQueues
{
    private $handlers = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function queue($queue, $handler, $options = [])
    {
    	$handler = new Handler();
    	$handler->queueName = $queue;
    	$handler->$className = $queue;
        $handlers->push($handler);
    }

    public function getHandlers()
    {
        return $handlers;
    }
}
