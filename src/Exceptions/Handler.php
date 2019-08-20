<?php

namespace Vinelab\Bowler\Exceptions;

use Exception;
use Illuminate\Contracts\Logging\Log;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Exception\AMQPProtocolConnectionException;
use PhpAmqpLib\Message\AMQPMessage;
use Vinelab\Bowler\Contracts\BowlerExceptionHandler as ExceptionHandler;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class Handler
{
    /**
     * The BowlerExceptionHandler contract bound app's exception handler.
     */
    private $exceptionHandler;

    /**
     * @var Log
     */
    private $logger;

    /**
     * Handler constructor.
     * @param  ExceptionHandler  $handler
     * @param  Log  $logger
     */
    public function __construct(ExceptionHandler $handler, Log $logger)
    {
        $this->exceptionHandler = $handler;
        $this->logger = $logger;
    }

    /**
     * Map php-mqplib exceptions to Bowler's.
     *
     * @param  Exception  $e
     * @param  array  $parameters
     * @param  array  $arguments
     *
     * @return void
     * @throws BowlerGeneralException
     * @throws DeclarationMismatchException
     * @throws InvalidSetupException
     */
    public function handleServerException(Exception $e, $parameters = [], $arguments = [])
    {
        if ($e instanceof AMQPProtocolChannelException) {
            $e = new DeclarationMismatchException($e, $parameters,  $arguments);
        } elseif ($e instanceof AMQPProtocolConnectionException) {
            $e = new InvalidSetupException($e, $parameters, $arguments);
        } else {
            $e = new BowlerGeneralException($e, $parameters, $arguments);
        }

        throw $e;
    }

    /**
     * Report error to the app's exceptions Handler.
     *
     * @param Exception $e
     * @param AMQPMessage $message
     */
    public function reportError($e, $message)
    {
        if (method_exists($this->exceptionHandler, 'reportQueue')) {
            $this->exceptionHandler->reportQueue($e, $message);
        } else {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }
    }
}
