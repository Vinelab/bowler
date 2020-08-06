<?php

namespace Vinelab\Bowler\Traits;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
trait AdminTrait
{
    /**
     * Delete a exchange.
     *
     * @param string $exchangeName
     * @param bool   $unused
     */
    public function deleteExchange($exchangeName, $unused = true)
    {
        $this->connection->getChannel()->exchange_delete($exchangeName, $unused);
    }

    /**
     * Delete a queue.
     *
     * @param string $queueName
     * @param bool   $unused
     * @param bool   $empty
     */
    public function deleteQueue($queueName, $unused = true, $empty = true)
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
}
