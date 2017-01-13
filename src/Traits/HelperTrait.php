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
