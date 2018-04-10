<?php

namespace Vinelab\Bowler\Tests;

use Vinelab\Bowler\BowlerServiceProvider;
use Orchestra\Testbench\TestCase as OTestCase;

/**
 * @author Abed Halawi <abed.halawi@vinelab.com>
 */
class TestCase extends OTestCase
{
    public function test_sample()
    {
        // just to skip warnings
        $this->assertEquals(1, 1);
    }

    protected function getPackageProviders($app)
    {
        $this->getEnvironmentSetUp($app);

        return [BowlerServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('queue.connections.rabbitmq.host', 'localhost');
        $app['config']->set('queue.connections.rabbitmq.port', 5672);
        $app['config']->set('queue.connections.rabbitmq.username', 'guest');
        $app['config']->set('queue.connections.rabbitmq.password', 'guest');
    }
}
