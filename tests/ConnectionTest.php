<?php

namespace Vinelab\Bowler\Tests;

use Mockery as M;
use ReflectionClass;
use Vinelab\Bowler\Connection;
use PhpAmqpLib\Wire\IO\StreamIO;
use Illuminate\Support\Facades\Config;
use Vinelab\Http\Client as HTTPClient;
use PhpAmqpLib\Connection\AMQPStreamConnection;

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
        $connection = $this->app[Connection::class];
        $this->assertEquals(30, $this->getProtectedProperty($connection, 'readWriteTimeout'));
        $this->assertEquals(30, $this->getProtectedProperty($connection, 'connectionTimeout'));
        $this->assertEquals(15, $this->getProtectedProperty($connection, 'heartbeat'));
    }

    public function test_set_altered_configurations_values()
    {
        Config::set('queue.connections.rabbitmq.host', 'localhost');
        Config::set('queue.connections.rabbitmq.port', 5672);
        Config::set('queue.connections.rabbitmq.username', 'default');
        Config::set('queue.connections.rabbitmq.password', 'default');
        // Config::set('queue.connections.rabbitmq.read_write_timeout', 60);
        // Config::set('queue.connections.rabbitmq.connection_timeout', 60);
        // Config::set('queue.connections.rabbitmq.heartbeat', 30);

        // $this->app['config']->set('queue.connections.rabbitmq.read_write_timeout', 120);
        // $this->app['config']->set('queue.connections.rabbitmq.connection_timeout', 120);

        $rbmqHost = config('queue.connections.rabbitmq.host');
        $rbmqPort = config('queue.connections.rabbitmq.port');
        $rbmqUsername = config('queue.connections.rabbitmq.username');
        $rbmqPassword = config('queue.connections.rabbitmq.password');
        $rbmqConnectionTimeout = config('queue.connections.rabbitmq.connection_timeout');
        $rbmqReadWriteTimeout = config('queue.connections.rabbitmq.read_write_timeout');
        $rbmqHeartbeat = config('queue.connections.rabbitmq.heartbeat');

        $this->app->bind(AMQPStreamConnection::class, function () use ($rbmqHost, $rbmqPort, $rbmqUsername, $rbmqPassword, $rbmqConnectionTimeout, $rbmqReadWriteTimeout, $rbmqHeartbeat) {
            return new AMQPStreamConnection($rbmqHost, $rbmqPort, $rbmqUsername, $rbmqPassword, '/', false, 'AMQPLAIN', null, 'en_US', config('queue.connections.rabbitmq.connection_timeout'), config('queue.connections.rabbitmq.read_write_timeout'), null, false, config('queue.connections.rabbitmq.heartbeat'));
        });

        $connection = $this->app[Connection::class];
        
        $conn = $connection->getConnection();
        $io = $this->getProtectedProperty($conn, 'io');

        $this->assertEquals(60, $this->getProtectedProperty($io, 'read_write_timeout'));
        $this->assertEquals(60, $this->getProtectedProperty($io, 'connection_timeout'));
        $this->assertEquals(30, $this->getProtectedProperty($io, 'heartbeat'));
    }

    protected static function getProtectedProperty($class, $value)
    {
        $reflection = new ReflectionClass($class);
        $property = $reflection->getProperty($value);
        $property->setAccessible(true);
        return $property->getValue($class);
    }

    // protected function getEnvironmentSetUp($app)
    // {
    //     parent::getEnvironmentSetUp($app);
        
    //     $app['config']->set('queue.connections.rabbitmq.host', 'localhost');
    //     $app['config']->set('queue.connections.rabbitmq.port', 5672);
    //     $app['config']->set('queue.connections.rabbitmq.username', 'guest');
    //     $app['config']->set('queue.connections.rabbitmq.password', 'guest');
    //     $app['config']->set('queue.connections.rabbitmq.heartbeat', 30);
    //     $app['config']->set('queue.connections.rabbitmq.read_write_timeout', 60);
    //     $app['config']->set('queue.connections.rabbitmq.connection_timeout', 60);
    // }
}
