<?php

namespace Vinelab\Bowler\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \App\Foundation\Pagination\PaginationQuery
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
