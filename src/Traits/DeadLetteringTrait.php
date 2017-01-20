<?php

namespace Vinelab\Bowler\Traits;

use Vinelab\Bowler\Exceptions\DeclarationMismatchException;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
trait DeadLetteringTrait
{
    /**
     * Configure Dead Lettering by creating a queue and exchange, and prepares the arguments array to be passed to the messaging queue.
     *
     * @param string $deadLetterQueueName
     * @param string $deadLetterExchangeName
     * @param string $deadLetterExchangeType
     * @param string $deadLetterRoutingKey
     * @param int    $messageTTL
     */
    public function configureDeadLettering($deadLetterQueueName, $deadLetterExchangeName, $deadLetterExchangeType = 'fanout', $deadLetterRoutingKey = null, $messageTTL = null)
    {
        $channel = $this->connection->getChannel();

        try {
            $channel->exchange_declare($deadLetterExchangeName, $deadLetterExchangeType, $this->passive, $this->durable, $this->autoDelete);

            $channel->queue_declare($deadLetterQueueName, $this->passive, $this->durable, false, $this->autoDelete);
        } catch (\Exception $e) {
            app(BowlerExceptionHandler::class)->handleServerException($e, compact($deadLetterQueueName, $deadLetterExchangeName, $deadLetterExchangeType, $deadLetterRoutingKey, $messageTTL),
                            $this->arguments);
        }

        $channel->queue_bind($deadLetterQueueName, $deadLetterExchangeName, $deadLetterRoutingKey);

        $this->compileArguments($deadLetterExchangeName, $deadLetterRoutingKey, $messageTTL);
    }

    /**
     * Compiles the arguments array to be passed to the messaging queue.
     *
     * @param string $deadLetterExchangeName
     * @param string $deadLetterRoutingKey
     * @param int    $messageTTL
     */
    private function compileArguments($deadLetterExchangeName, $deadLetterRoutingKey, $messageTTL)
    {
        // 'S', Rabbitmq data type for long string
        $this->arguments['x-dead-letter-exchange'] = ['S', $deadLetterExchangeName];

        if ($deadLetterRoutingKey) {
            $this->arguments['x-dead-letter-routing-key'] = ['S', $deadLetterRoutingKey];
        }

        if ($messageTTL) {
            // 'I', Rabbitmq data type for long int
            $this->arguments['x-message-ttl'] = ['I', $messageTTL];
        }
    }
}
