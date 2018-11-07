<?php

namespace Vinelab\Bowler\Tests;

use Mockery as M;
use Vinelab\Bowler\Connection;
use Vinelab\Http\Client as HTTPClient;

/**
 * @author Abed Halawi <abed.halawi@vinelab.com>
 */
class ConnectionTest extends TestCase
{
    public function tearDown()
    {
        M::close();
    }

    public function test_fetching_consumers_default()
    {
        $queueName = 'the-queue';
        $mClient = M::mock(HTTPClient::class);
        $request = [
            'url' => 'localhost:15672/api/queues/%2F/'.$queueName,
            'params' => ['columns' => 'consumer_details.consumer_tag'],
            'auth' => [
                'username' => 'guest',
                'password' => 'guest',
            ],
        ];

        $mClient->shouldReceive('get')->once()->with($request)->andReturn($mClient);
        $mClient->shouldReceive('json')->once()->withNoArgs()->andReturn('response');

        $this->app->bind(HTTPClient::class, function () use ($mClient) {
            return $mClient;
        });

        $mConnection = M::mock(Connection::class)->makePartial();
        $this->app->bind(Connection::class, function () use ($mConnection) {
            return $mConnection;
        });

        $connection = $this->app[Connection::class];
        $response = $connection->fetchQueueConsumers($queueName);

        $this->assertEquals('response', $response);
    }
}
