<?php

namespace Vinelab\Bowler\Tests\Console\Commands;

use Mockery as M;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use Vinelab\Bowler\Connection;
use Vinelab\Bowler\Console\Commands\ConsumerHealthCheckCommand;
use Vinelab\Bowler\Tests\TestCase;

class ConsumerHealthCheckCommandTest extends TestCase
{
    public function tearDown()
    {
        M::close();
    }

    public function test_checking_consumer_successfully()
    {
        $command = M::mock('Vinelab\Bowler\Console\Commands\ConsumerHealthCheckCommand[error]');

        $queueName = 'queue-to-consume';

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $mConnection = M::mock(Connection::class);
        $mChannel = M::mock(AMQPChannel::class);
        $mChannel::$PROTOCOL_CONSTANTS_CLASS = 'PhpAmqpLib\Wire\Constants091';
        $mChannel->shouldReceive('queue_declare')->once()->andReturn([$queueName, 10, 1]);
        $mConnection->shouldReceive('getChannel')->once()->andReturn($mChannel);

        $this->app->bind(Connection::class, function() use($mConnection) {
            return $mConnection;
        });

        $code = $this->artisan('bowler:healthcheck:consumer', ['queueName' => $queueName]);

        $this->assertEquals(0, $code);
    }

    public function test_with_0_expected_1_connected()
    {
        // should err out requesting minimum to be greater than 0
        $command = M::mock('Vinelab\Bowler\Console\Commands\ConsumerHealthCheckCommand[error]');
        $command->shouldReceive('error')->once()->with('Health check failed. Minimum consumer count not met: expected 0 got 1');

        $queueName = 'queue-to-consume';

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $mConnection = M::mock(Connection::class);
        $mChannel = M::mock(AMQPChannel::class);
        $mChannel::$PROTOCOL_CONSTANTS_CLASS = 'PhpAmqpLib\Wire\Constants091';
        $mChannel->shouldReceive('queue_declare')->once()->andReturn([$queueName, 10, 1]);
        $mConnection->shouldReceive('getChannel')->once()->andReturn($mChannel);

        $this->app->bind(Connection::class, function() use($mConnection) {
            return $mConnection;
        });

        $code = $this->artisan('bowler:healthcheck:consumer', ['queueName' => $queueName, '--consumers' => 0]);

        $this->assertEquals(1, $code);
    }

    public function test_with_1_expected_0_connected()
    {
        // should err out requesting minimum to be greater than 0
        $command = M::mock('Vinelab\Bowler\Console\Commands\ConsumerHealthCheckCommand[error]');
        $command->shouldReceive('error')->once()->with('Health check failed. Minimum consumer count not met: expected 1 got 0');

        $queueName = 'queue-to-consume';

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $mConnection = M::mock(Connection::class);
        $mChannel = M::mock(AMQPChannel::class);
        $mChannel::$PROTOCOL_CONSTANTS_CLASS = 'PhpAmqpLib\Wire\Constants091';
        $mChannel->shouldReceive('queue_declare')->once()->andReturn([$queueName, 10, 0]);
        $mConnection->shouldReceive('getChannel')->once()->andReturn($mChannel);

        $this->app->bind(Connection::class, function() use($mConnection) {
            return $mConnection;
        });

        $code = $this->artisan('bowler:healthcheck:consumer', ['queueName' => $queueName, '--consumers' => 1]);

        $this->assertEquals(1, $code);
    }

    public function test_healthcheck_with_queue_does_not_exist()
    {
        // should err out with an error message indicating the case
        $command = M::mock('Vinelab\Bowler\Console\Commands\ConsumerHealthCheckCommand[error]');
        $command->shouldReceive('error')->once()->with('Queue with name the-queue does not exist.');

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $mConnection = M::mock(Connection::class);
        $mChannel = M::mock(AMQPChannel::class);
        $mChannel::$PROTOCOL_CONSTANTS_CLASS = 'PhpAmqpLib\Wire\Constants091';
        $exception = new AMQPProtocolChannelException(404, "NOT_FOUND - no queue 'the-queue' in vhost '/'", [50, 10]);
        $mChannel->shouldReceive('queue_declare')->once()
            ->with('the-queue', true, false, false, false, [])
            ->andThrow($exception);
        $mConnection->shouldReceive('getChannel')->once()->andReturn($mChannel);

        $this->app->bind(Connection::class, function() use($mConnection) {
            return $mConnection;
        });

        $code = $this->artisan('bowler:healthcheck:consumer', ['queueName' => 'the-queue']);

        $this->assertEquals(1, $code);
    }

    public function test_error_connecting_to_rabbitmq()
    {
        $command = M::mock('Vinelab\Bowler\Console\Commands\ConsumerHealthCheckCommand[error]');
        $command->shouldReceive('error')->once()->with('Unable to connect to RabbitMQ.');

        $this->app->bind(Connection::class, function () {
            return new Connection('', '', '', '');
        });

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $code = $this->artisan('bowler:healthcheck:consumer', ['queueName' => 'the-queue']);

        $this->assertEquals(1, $code);
    }
}
