<?php

namespace Vinalab\Bowler\Exceptions;

use Vinelab\Bowler\Exceptions\InvalidSetupException;
use Vinelab\Bowler\Exceptions\BowlerGeneralException;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Exception\AMQPProtocolConnectionException;
use Vinelab\Bowler\Exceptions\DeclarationMismatchException;
use Vinelab\Bowler\Contracts\BowlerExceptionHandler as ExceptionHandler;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class Handler
{
    /**
     * The BowlerExceptionHandler contract bound app's exception handler
     */
    private $exceptionHandler;

    public function __construct(ExceptionHandler $handler)
    {
        $this->exceptionHandler = $handler;
    }

    /**
     * Map php-mqplib exceptions to Bowler's
     *
     * @param \Exception    $e
     * @param array         $parameters
     * @param array         $arguments
     *
     * @return mix
     */
    public function handleServerException(\Exception $e, $parameters = [], $arguments = [])
    {
        if ($e instanceof AMQPProtocolChannelException) {
            $e = new DeclarationMismatchException($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e->getTrace(), $e->getPrevious(), $e->getTraceAsString(), $parameters,  $arguments);
        }

        elseif ($e instanceof AMQPProtocolConnectionException) {
            $e = new InvalidSetupException($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e->getTrace(), $e->getPrevious(), $e->getTraceAsString(), $parameters, $arguments);
        }

        else {
            $e = new BowlerGeneralException($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e->getTrace(), $e->getPrevious(), $e->getTraceAsString(), $parameters, $arguments);
        }

        $this->reportError($e);
        $this->renderError($e);

        return $e;
    }

    /**
     * Report error to the app's exceptions Handler
     *
     * @param \Exception $e
     * @param mix PhpAmqpLib\Message\AMQPMessage | null $msg
     */
    public function reportError($e, $msg = null)
    {
        $this->exceptionHandler->reportQueue($e, $msg);
    }

    /**
     * Render error from the app's exceptions Handler
     *
     * @param \Exception $e
     * @param mix PhpAmqpLib\Message\AMQPMessage | null $msg
     */
    public function renderError($e, $msg = null)
    {
        $this->exceptionHandler->renderQueue($e, $msg);
    }
}
