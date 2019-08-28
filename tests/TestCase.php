<?php

namespace Vinelab\Bowler\Tests;

use Vinelab\Bowler\BowlerServiceProvider;
use Orchestra\Testbench\TestCase as OTestCase;

/**
 * @author Abed Halawi <abed.halawi@vinelab.com>
 */
class TestCase extends OTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            BowlerServiceProvider::class,
        ];
    }
}
