<?php

namespace Vinelab\Bowler;

use Illuminate\Support\ServiceProvider;
use Vinelab\Bowler\Console\Commands\BowlerCommand;


class BowlerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->singleton('vinelab.bowler.registrator', function ($app) {
            return new RegisterQueues($app->make('Vinelab\Bowler\Connection'));
        });

        $this->app->bind(Connection::class, function () {
            return new Connection();
        });

        $this->app->bind(
            Vinelab\Bowler\Contracts\BowlerExceptionHandler::class,
            $this->app->getNamespace().\Exceptions\Handler::class
        );

        //register command
        $commands = [
            BowlerCommand::class,
        ];
        $this->commands($commands);
    }
}
