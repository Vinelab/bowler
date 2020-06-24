<?php

namespace Vinelab\Bowler\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void queue(string $queue, string $className, array $options)
 * @method static void subscriber(string $queue, string $className, array $bindingKeys, string $exchangeName, string $exchangeType = 'topic')
 * @method static array getHandlers()
 *
 * @see \Vinelab\Bowler\RegisterQueues
 */
class Registrator extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'vinelab.bowler.registrator';
    }
}
