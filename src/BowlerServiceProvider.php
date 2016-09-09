<?php

namespace Vinelab\Bowler;

use Illuminate\Support\ServiceProvider;
use Vinelab\Bowler\Connection;
use Vinelab\Bowler\Console\Commands\BowlerCommand;
use Vinelab\Bowler\RegisterQueues;
use Console\InstallCommand;

class BowlerServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton('vinelab.bowler.registrator', function ($app)
         {
            return new RegisterQueues($app->make('Vinelab\Bowler\Connection'));
        });

        $this->app->bind(Connection::class, function ()
         {
            return new Connection();
        });

        //register command
        $commands = [
            'Vinelab\Bowler\Console\Commands\BowlerCommand',
        ];
         $this->commands($commands);

    }

}
