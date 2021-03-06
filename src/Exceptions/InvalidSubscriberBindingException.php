<?php

namespace Vinelab\Bowler\Exceptions;

use Vinelab\Bowler\Exceptions\BowlerGeneralException;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class InvalidSubscriberBindingException extends BowlerGeneralException
{
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }
}
