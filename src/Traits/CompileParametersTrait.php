<?php

namespace Vinelab\Bowler\Traits;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
trait CompileParametersTrait
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
                'exchangeName' => property_exists($this, 'exchangeName') ? $this->exchangeName : null,
                'exchangeType' => property_exists($this, 'exchangeType') ? $this->exchangeType : null,
                'passive' => property_exists($this, 'passive') ? $this->passive : null,
                'durable' => property_exists($this, 'durable') ? $this->durable : null,
                'autoDelete' => property_exists($this, 'autoDelete') ? $this->autoDelete : null,
                'deliveryMode' => property_exists($this, 'deliveryMode') ? $this->deliveryMode : null,
            ];

        return array_filter($params);
    }
}
