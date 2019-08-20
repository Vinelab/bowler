<?php

namespace Vinelab\Bowler\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Vinelab\Bowler\LifecycleManager;

/**
 * Class Message
 *
 * @method static void beforePublish(Closure $callback)
 * @method static void published(Closure $callback)
 * @method static void beforeConsume(Closure $callback)
 * @method static void consumed(Closure $callback)
 *
 * @see LifecycleManager
 */
class Message extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'vinelab.bowler.lifecycle';
    }
}