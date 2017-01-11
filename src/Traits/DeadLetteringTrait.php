<?php

namespace Vinelab\Bowler\Traits;

trait DeadLetteringTrait
{
    /**
     * Configure Dead Lettering by creating a queue and exchange, and prepares the arguments array to be passed to the messaging queue.
     *
     * @param string    $deadLetterQueueName
     * @param string    $deadLetterExchangeName
     * @param string    $deadLetterExchangeType
     * @param string    $deadLetterRoutingKey
     * @param int       $messageTtl
     *
     * @return void
     */
    public function configureDeadLettering($deadLetterQueueName, $deadLetterExchangeName, $deadLetterExchangeType, $deadLetterRoutingKey = null, int $messageTtl = null)
    {
        $channel = $this->connection->getChannel();

        $channel->exchange_declare($deadLetterExchangeName, $deadLetterExchangeType, $this->passive, $this->durable, false, $this->autoDelete);

        $channel->queue_declare($deadLetterQueueName, $this->passive, $this->durable, false, $this->autoDelete);

        $channel->queue_bind($deadLetterQueueName, $deadLetterExchangeName);

        $this->compileArguments($deadLetterExchangeName, $deadLetterRoutingKey, $messageTtl);
    }

    /**
     * Compiles the arguments array to be passed to the messaging queue.
     *
     * @param string    $deadLetterExchangeName
     * @param string    $deadLetterRoutingKey
     * @param int       $messageTtl
     *
     * @return void
     */
    private function compileArguments($deadLetterExchangeName, $deadLetterRoutingKey, $messageTtl)
    {
        $this->arguments['x-dead-letter-exchange'] = ['S', $deadLetterExchangeName];

        if($deadLetterRoutingKey) {
            $this->Arguments['x-dead-letter-routing-key'] = ['S', $deadLetterRoutingKey];
        }

        if($messageTtl) {
            $this->arguments['x-message-ttl'] = ['I', $messageTtl];
        }
    }
}
