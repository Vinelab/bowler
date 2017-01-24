<?php

namespace Vinelab\Bowler\Exceptions;

use Exception;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class BowlerGeneralException extends Exception
{
    // Parameters used in the setup
    protected $parameters;

    // Dead lettering arguments
    protected $arguments;

    public function __construct($e, $parameters = [], $arguments = [])
    {
        parent::__construct($e);

        $this->parameters = $parameters;
        $this->arguments = $arguments;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getArguments()
    {
        return $this->arguments;
    }
}
