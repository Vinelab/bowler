<?php

namespace Vinelab\Bowler\Tests\Console\Commands;

use Mockery as M;
use Vinelab\Bowler\Connection;
use Vinelab\Bowler\Tests\TestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use Illuminate\Testing\PendingCommand;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;

/**
 * @author Abed Halawi <abed.halawi@vinelab.com>
 */
class ConsumerHealthCheckCommandTest extends TestCase
{
    public function tearDown(): void
    {
        M::close();
    }

    public function test_checking_consumer_successfully()
    {
        $command = M::mock('Vinelab\Bowler\Console\Commands\ConsumerHealthCheckCommand[error,readConsumerTag]');
        $queueName = 'the-queue';
        $consumerTag = 'tag-1234';

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $mConnection = M::mock(Connection::class);

        $command->shouldReceive('readConsumerTag')->once()->andReturn($consumerTag);
        $mConnection->shouldReceive('fetchQueueConsumers')->once()->andReturn(json_decode(json_encode([
            'consumer_details' => [
                ['consumer_tag' => $consumerTag],
                ['consumer_tag' => 'another-tag-here'],
            ],
        ])));

        $mChannel = M::mock(AMQPChannel::class);

        $mChannel->shouldReceive('queue_declare')->once()->andReturn([$queueName, 10, 1]);
        $mConnection->shouldReceive('getChannel')->once()->andReturn($mChannel);

        $this->app->bind(Connection::class, function () use ($mConnection) {
            return $mConnection;
        });

        $result = $this->artisan('bowler:healthcheck:consumer', ['queueName' => $queueName]);

        if ($result instanceof PendingCommand) {
            $this->assertEquals(0, $result->run());
        } else {
            $this->assertEquals(0, $result);
        }

    }

    public function test_with_no_consumers_connected()
    {
        // should err out requesting minimum to be greater than 0
        $command = M::mock('Vinelab\Bowler\Console\Commands\ConsumerHealthCheckCommand[error]');
        $command->shouldReceive('error')->once()->with('No consumers connected to queue "queue-to-consume"');

        $queueName = 'queue-to-consume';

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $mConnection = M::mock(Connection::class);
        $mConnection->shouldReceive('fetchQueueConsumers')->once()->andReturn(json_decode(json_encode([])));

        $mChannel = M::mock(AMQPChannel::class);
        $mChannel->shouldReceive('queue_declare')->once()->andReturn([$queueName, 10, 1]);

        $mConnection->shouldReceive('getChannel')->once()->andReturn($mChannel);

        $this->app->bind(Connection::class, function () use ($mConnection) {
            return $mConnection;
        });

        $result = $this->artisan('bowler:healthcheck:consumer', ['queueName' => $queueName]);

        if ($result instanceof PendingCommand) {
            $this->assertEquals(1, $result->run());
        } else {
            $this->assertEquals(1, $result);
        }
    }

    public function test_with_consumer_tag_not_found()
    {
        // should err out requesting minimum to be greater than 0
        $command = M::mock('Vinelab\Bowler\Console\Commands\ConsumerHealthCheckCommand[error,readConsumerTag]');

        $queueName = 'queue-to-consume';
        $consumerTag = 'amqp.98oyiuahksjdf';

        $command->shouldReceive('error')
            ->once()
            ->with('Health check failed! Could not find consumer with tag "' . $consumerTag . '"');

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $mConnection = M::mock(Connection::class);

        $command->shouldReceive('readConsumerTag')->once()->andReturn($consumerTag);
        $mConnection->shouldReceive('fetchQueueConsumers')->once()->andReturn(json_decode(json_encode([
            'consumer_details' => [
                ['consumer_tag' => 'nope-not-me'],
                ['consumer_tag' => 'another-tag-here'],
            ],
        ])));

        $mChannel = M::mock(AMQPChannel::class);
        $mChannel->shouldReceive('queue_declare')->once()->andReturn([$queueName, 10, 0]);
        $mConnection->shouldReceive('getChannel')->once()->andReturn($mChannel);

        $this->app->bind(Connection::class, function () use ($mConnection) {
            return $mConnection;
        });

        $result = $this->artisan('bowler:healthcheck:consumer', ['queueName' => $queueName]);

        if ($result instanceof PendingCommand) {
            $this->assertEquals(1, $result->run());
        } else {
            $this->assertEquals(1, $result);
        }
    }

    public function test_healthcheck_with_queue_does_not_exist()
    {
        // should err out with an error message indicating the case
        $command = M::mock('Vinelab\Bowler\Console\Commands\ConsumerHealthCheckCommand[error]');
        $command->shouldReceive('error')->once()->with('Queue with name the-queue does not exist.');

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $mConnection = M::mock(Connection::class);

        $mChannel = M::mock(AMQPChannel::class);
        $exception = new AMQPProtocolChannelException(404, "NOT_FOUND - no queue 'the-queue' in vhost '/'", [50, 10]);
        $mChannel->shouldReceive('queue_declare')->once()
            ->with('the-queue', true, false, false, false, [])
            ->andThrow($exception);
        $mConnection->shouldReceive('getChannel')->once()->andReturn($mChannel);

        $this->app->bind(Connection::class, function () use ($mConnection) {
            return $mConnection;
        });

        $result = $this->artisan('bowler:healthcheck:consumer', ['queueName' => 'the-queue']);

        if ($result instanceof PendingCommand) {
            $this->assertEquals(1, $result->run());
        } else {
            $this->assertEquals(1, $result);
        }
    }

    public function test_error_connecting_to_rabbitmq()
    {
        $command = M::mock('Vinelab\Bowler\Console\Commands\ConsumerHealthCheckCommand[error]');
        $command->shouldReceive('error')->once()->with('Unable to connect to RabbitMQ.');

        $this->app->bind(Connection::class, function () {
            return new Connection('', '', '', '');
        });

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $result = $this->artisan('bowler:healthcheck:consumer', ['queueName' => 'the-queue']);

        if ($result instanceof PendingCommand) {
            $this->assertEquals(1, $result->run());
        } else {
            $this->assertEquals(1, $result);
        }
    }
}
