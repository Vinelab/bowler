<?php

namespace Vinelab\Bowler;

use Vinelab\Bowler\Handler;

class RegisterQueues
{
    private $handlers = [];

    public function queue($queue, $className, $options = [])
    {
    	$handler = new Handler();
    	$handler->queueName = $queue;
    	$handler->className = $className;
      	array_push($this->handlers, $handler);
    }

    public function getHandlers()
    {
        return $this->handlers;
    }
}
