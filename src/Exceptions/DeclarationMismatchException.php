<?php

namespace Vinelab\Bowler\Exceptions;

use Exception;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class DeclarationMismatchException extends Exception
{
    protected $message;
    protected $code;
    protected $file;
    protected $line;
    protected $trace;
    protected $previous;
    protected $traceAsString;
    protected $parameters;

    // Dead lettering arguments
    protected $arguments;

    public function __construct($message, $code, $file, $line, $trace, $previous, $traceAsString, $parameters, $arguments = [])
    {
        $this->message = $message;
        $this->code= $code;
        $this->file = $file;
        $this->line = $line;
        $this->trace = $trace;
        $this->previous = $previous;
        $this->traceAsString = $traceAsString;
        $this->parameters = $parameters;
        $this->arguments = $arguments;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getLine()
    {
        return $this->line;
    }

    public function getTrace()
    {
        return $this->trace;
    }

    public function getPrevious()
    {
        return $this->previous;
    }

    public function getTraceAsString()
    {
        return $this->traceAsString;
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
