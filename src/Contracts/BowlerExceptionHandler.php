<?php

namespace Vinelab\Bowler\Contracts;

use Exception;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
interface BowlerExceptionHandler
{
    public function reportQueue(Exception $e);
}
