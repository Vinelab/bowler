<?php

namespace Vinelab\Bowler\Tests;

use Mockery as M;
use Vinelab\Bowler\Connection;
use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Config;
use Vinelab\Http\Client as HTTPClient;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * @author Abed Halawi <abed.halawi@vinelab.com>
 */
class ConnectionTest extends TestCase
{
    public function tearDown(): void
    {
        M::close();
    }

    public function test_fetching_consumers_default()
    {
        $queueName = 'the-queue';
        $mClient = M::mock(HTTPClient::class);
        $request = [
            'url' => 'localhost:15672/api/queues/%2F/' . $queueName,
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

    public function test_set_default_configurations_values()
    {
        $mConnection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->app->bind(Connection::class, function () use ($mConnection) {
            return $mConnection;
        });
        $connection = $this->app[Connection::class];

        $this->assertEquals(15, $connection->getHeartbeat());
        $this->assertEquals(30, $connection->getReadWriteTimeout());
        $this->assertEquals(30, $connection->getConnectionTimeout());
        $this->assertEquals('/', $connection->getVhost());
    }

    public function test_set_altered_configurations_values()
    {
        Config::set('bowler.rabbitmq.host', 'notlocal');
        Config::set('bowler.rabbitmq.port', 6666);
        Config::set('bowler.rabbitmq.read_write_timeout', 60);
        Config::set('bowler.rabbitmq.connection_timeout', 60);
        Config::set('bowler.rabbitmq.heartbeat', 30);
        Config::set('bowler.rabbitmq.vhost', '/test-vhost');

        $mAMQPStreamConnection = M::mock(AMQPStreamConnection::class);
        $this->app->bind(AMQPStreamConnection::class, function () use ($mAMQPStreamConnection) {
            return $mAMQPStreamConnection;
        });

        $mChannel = M::mock(Channel::class);
        $mAMQPStreamConnection->shouldReceive('channel')->once()->withNoArgs()->andReturn($mChannel);
        $mAMQPStreamConnection->shouldReceive('close')->once()->withNoArgs();
        $mChannel->shouldReceive('close')->once()->withNoArgs();

        $connection = $this->app[Connection::class];

        $this->assertEquals(30, $connection->getHeartbeat());
        $this->assertEquals(60, $connection->getReadWriteTimeout());
        $this->assertEquals(60, $connection->getConnectionTimeout());
        $this->assertEquals('/test-vhost', $connection->getVhost());
    }
}
