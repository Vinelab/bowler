<?php

namespace Vinelab\Bowler\Contracts;

use Exception;
use PhpAmqpLib\Message\AMQPMessage as Message;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
interface BowlerExceptionHandler
{
    public function reportQueue(Exception $e, Message $msg);

    public function renderQueue(Exception $e, Message $msg);
}
