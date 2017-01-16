<?php

namespace Vinelab\Bowler\Traits;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
trait HelperTrait
{
    /**
     * Compiles the parameters passed to the constructor.
     *
     * @return array
     */
    private function compileParameters()
    {
        $params = [
                'queueName' => property_exists($this, 'queueName') ? $this->queueName : null,
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
