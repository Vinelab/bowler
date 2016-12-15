<?php

namespace Vinelab\Bowler\Contracts;

use Exception;

interface BowlerExceptionHandler
{
    public function report(Exception $e);
}
