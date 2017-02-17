<?php

namespace Vinelab\Bowler\Exceptions;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class UnregisteredQueueException extends BowlerGeneralException
{
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }
}
