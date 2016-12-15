<?php

namespace Illuminate\Foundation\Exceptions;

use Exception;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Container\Container;
use Vinelab\Bowler\Contracts\BowlerExceptionHandler as ExceptionHandlerContract;

class Handler implements ExceptionHandlerContract
{
    /**
     * Create a new exception handler instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $e
     * @return void
     *
     * @throws \Exception
     *
     * @todo should we implement the LoggerInterface
     */
    public function reportQueue(Exception $e)
    {
        try {
            $logger = $this->container->make(LoggerInterface::class);
        } catch (Exception $ex) {
            throw $e; // throw the original exception
        }

        $logger->error($e);
    }

}
