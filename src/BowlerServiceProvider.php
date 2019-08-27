<?php

namespace Vinelab\Bowler;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\ServiceProvider;
use Vinelab\Bowler\Console\Commands\QueueCommand;
use Vinelab\Bowler\Console\Commands\ConsumeCommand;
use Vinelab\Bowler\Console\Commands\SubscriberCommand;
use Vinelab\Bowler\Console\Commands\ConsumerHealthCheckCommand;
use Vinelab\Bowler\Exceptions\Handler as BowlerExceptionHandler;

/**
 * @author Ali Issa <ali@vinelab.com>
 * @author Kinane Domloje <kinane@vinelab.com>
 * @author Charalampos Raftopoulos <harris@vinelab.com>
 */
class BowlerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/bowler.php' => config_path('bowler.php'),
        ]);
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/bowler.php', 'bowler');

        // register facade to resolve instance
        $this->app->singleton('vinelab.bowler.registrator', function ($app) {
            return new RegisterQueues();
        });

        $this->app->singleton('vinelab.bowler.lifecycle', function ($app) {
            return new MessageLifecycleManager($app['log'], $app['config']);
        });

        // use the same Registrator instance all over the app (to make it injectable).
        $this->app->singleton(RegisterQueues::class, function ($app) {
            return $app['vinelab.bowler.registrator'];
        });

        $this->app->bind(Connection::class, function () {
            // Bind connection to env configuration
            $rbmqHost = config('bowler.rabbitmq.host');
            $rbmqPort = config('bowler.rabbitmq.port');
            $rbmqUsername = config('bowler.rabbitmq.username');
            $rbmqPassword = config('bowler.rabbitmq.password');
            $rbmqConnectionTimeout = config('bowler.rabbitmq.connection_timeout');
            $rbmqReadWriteTimeout = config('bowler.rabbitmq.read_write_timeout');
            $rbmqHeartbeat = config('bowler.rabbitmq.heartbeat');
            $rbmqVhost = config('bowler.rabbitmq.vhost', '/');

            return new Connection($rbmqHost, $rbmqPort, $rbmqUsername, $rbmqPassword, $rbmqConnectionTimeout, $rbmqReadWriteTimeout, $rbmqHeartbeat, $rbmqVhost);
        });

        $this->app->when(BowlerExceptionHandler::class)
            ->needs(ExceptionHandler::class)
            ->give($this->app->getNamespace().'Exceptions\Handler');

        //register command
        $this->commands([
            QueueCommand::class,
            ConsumeCommand::class,
            SubscriberCommand::class,
            ConsumerHealthCheckCommand::class,
        ]);
    }
}
