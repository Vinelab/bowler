<?php

namespace Vinelab\Bowler\Traits;

use Vinelab\Bowler\Exceptions\DeclarationMismatchException;

trait DeadLetteringTrait
{
    /**

    /**
     * Delete a exchange.
     *
     * @param string $exchangeName
     * @param bool $unused
     */
    public function deleteExchange($exchangeName, $unused)
    {
        $this->connection->getChannel()->exchange_delete($queueName, $unused, $empty);
    }

    /**
     * Delete a queue.
     *
     * @param string $queueName
     * @param bool $unused
     * @param bool $empty
     */
    public function deleteQueue($queueName, $unused, $empty)
    {
        $this->connection->getChannel()->queue_delete($queueName, $unused, $empty);
    }

    /**
     * Purge a queue.
     *
     * @param string $queueName
     */
    public function purgeQueue($queueName)
    {
        $this->connection->getChannel()->queue_purge($queueName);
    }

    /**
     * Configure Dead Lettering by creating a queue and exchange, and prepares the arguments array to be passed to the messaging queue.
     *
     * @param string    $deadLetterQueueName
     * @param string    $deadLetterExchangeName
     * @param string    $deadLetterExchangeType
     * @param string    $deadLetterRoutingKey
     * @param int       $messageTTL
     *
     * @return void
     */
    public function configureDeadLettering($deadLetterQueueName, $deadLetterExchangeName, $deadLetterExchangeType = 'fanout', $deadLetterRoutingKey = null, $messageTTL = null)
    {
        $channel = $this->connection->getChannel();

        try {
            $channel->exchange_declare($deadLetterExchangeName, $deadLetterExchangeType, $this->passive, $this->durable, false, $this->autoDelete);

            $channel->queue_declare($deadLetterQueueName, $this->passive, $this->durable, false, $this->autoDelete);
        } catch (\Exception $e) {
            throw new DeclarationMismatchException($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e->getTrace(), $e->getPrevious(), $e->getTraceAsString(),
                            [
                                'deadLetterQueueName' => $deadLetterQueueName,
                                'deadLetterExchangeName' => $deadLetterExchangeName,
                                'deadLetterExchangeEype' => $deadLetterExchangeType,
                                'deadLetterRoutingKey' => $deadLetterRoutingKey,
                                'messageTTL' => $messageTTL
                            ],
                            $this->arguments);
        }

        $channel->queue_bind($deadLetterQueueName, $deadLetterExchangeName);

        $this->compileArguments($deadLetterExchangeName, $deadLetterRoutingKey, $messageTTL);
    }

    /**
     * Compiles the arguments array to be passed to the messaging queue.
     *
     * @param string    $deadLetterExchangeName
     * @param string    $deadLetterRoutingKey
     * @param int       $messageTTL
     *
     * @return void
     */
    private function compileArguments($deadLetterExchangeName, $deadLetterRoutingKey, $messageTTL)
    {
        // 'S', Rabbitmq data type for long string
        $this->arguments['x-dead-letter-exchange'] = ['S', $deadLetterExchangeName];

        if($deadLetterRoutingKey) {
            $this->Arguments['x-dead-letter-routing-key'] = ['S', $deadLetterRoutingKey];
        }

        if($messageTTL) {
            // 'I', Rabbitmq data type for long int
            $this->arguments['x-message-ttl'] = ['I', $messageTTL];
        }
    }

    /**
     * Compiles the parameters passed to the constructor.
     *
     * @return array
     */
    private function compileParameters()
    {
        $params = [
                'queueName' => $this->queueName,
                'exchangeName' => $this->exchangeName,
                'exchangeType' => $this->exchangeType,
                'passive' => $this->passive,
                'durable' => $this->durable,
                'autoDelete' => $this->autoDelete,
                'deliveryMode' => $this->deliveryMode
            ];

        property_exists($this, 'routingKeys') ? ($params['routingKeys'] = $this->routingKeys) : ($params['bindingKeys'] = $this->bindingKeys);

        return $params;
    }
}
