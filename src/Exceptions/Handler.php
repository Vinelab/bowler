<?php

namespace Vinelab\Bowler\Exceptions;

use Exception;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Support\Str;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Exception\AMQPProtocolConnectionException;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Vinelab\Bowler\Contracts\BowlerExceptionHandler;

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
     * @var Repository
     */
    protected $config;

    /**
     * Handler constructor.
     * @param  ExceptionHandler  $handler
     * @param  Log  $logger
     * @param  Repository  $config
     */
    public function __construct(ExceptionHandler $handler, Log $logger, Repository $config)
    {
        $this->exceptionHandler = $handler;
        $this->logger = $logger;
        $this->config = $config;
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
    public function reportError($e, AMQPMessage $message)
    {
        if ($this->exceptionHandler instanceof BowlerExceptionHandler) {
            $this->exceptionHandler->reportQueue($e, $message);
        } else {
            $this->logger->error(
                $e->getMessage(),
                array_merge($this->context($message), ['exception' => $e])
            );
        }
    }

    /**
     * @param  AMQPMessage  $message
     * @return array
     */
    protected function context(AMQPMessage $message): array
    {
        try {
            return array_filter([
                'msg_body' => Str::limit(
                    $message->body,
                    $this->config->get('bowler.log.message.truncate_length')
                )
            ]);
        } catch (Throwable $e) {
            return [];
        }
    }
}
