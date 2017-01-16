<?php

namespace Vinelab\Bowler;

/**
 * @author Ali Issa <ali@vinelab.com>
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class RegisterQueues
{
    private $handlers = [];

    public function queue($queue, $className, $options = [])
    {
        $handler = new Handler();
        $handler->queueName = $queue;
        $handler->className = $className;
        $handler->options = $options;

        array_push($this->handlers, $handler);
    }

    public function getHandlers()
    {
        return $this->handlers;
    }
}
