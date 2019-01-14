<?php

namespace Vinelab\Bowler;

use Illuminate\Support\ServiceProvider;
use Vinelab\Bowler\Console\Commands\QueueCommand;
use Vinelab\Bowler\Console\Commands\ConsumeCommand;
use Vinelab\Bowler\Console\Commands\SubscriberCommand;
use Vinelab\Bowler\Console\Commands\ConsumerHealthCheckCommand;

/**
 * @author Ali Issa <ali@vinelab.com>
 * @author Kinane Domloje <kinane@vinelab.com>
 * @author Charalampos Raftopoulos <harris@vinelab.com>
 */
class BowlerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // register facade to resolve instance
        $this->app->singleton('vinelab.bowler.registrator', function ($app) {
            return new RegisterQueues();
        });

        // use the same Registrator instance all over the app (to make it injectable).
        $this->app->singleton(RegisterQueues::class, function ($app) {
            return $app['vinelab.bowler.registrator'];
        });

        $this->app->bind(Connection::class, function () {
            // Bind connection to env configuration
            $rbmqHost = config('queue.connections.rabbitmq.host');
            $rbmqPort = config('queue.connections.rabbitmq.port');
            $rbmqUsername = config('queue.connections.rabbitmq.username');
            $rbmqPassword = config('queue.connections.rabbitmq.password');
            $rbmqConnectionTimeout = config('queue.connections.rabbitmq.connection_timeout') ? (int) config('queue.connections.rabbitmq.connection_timeout') : 30;
            $rbmqReadWriteTimeout = config('queue.connections.rabbitmq.read_write_timeout') ? (int) config('queue.connections.rabbitmq.read_write_timeout') : 30;
            $rbmqHeartbeat = config('queue.connections.rabbitmq.heartbeat') ? (int) config('queue.connections.rabbitmq.heartbeat') : 15;

            return new Connection($rbmqHost, $rbmqPort, $rbmqUsername, $rbmqPassword, $rbmqConnectionTimeout, $rbmqReadWriteTimeout, $rbmqHeartbeat);
        });

        $this->app->bind(
            \Vinelab\Bowler\Contracts\BowlerExceptionHandler::class,
            $this->app->getNamespace().\Exceptions\Handler::class
        );

        //register command
        $commands = [
            QueueCommand::class,
            ConsumeCommand::class,
            SubscriberCommand::class,
            ConsumerHealthCheckCommand::class,
        ];
        $this->commands($commands);
    }
}
