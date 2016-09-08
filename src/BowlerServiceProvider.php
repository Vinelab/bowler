<?php

namespace Vinelab\Bowler;

use Illuminate\Support\ServiceProvider;
use Vinelab\Bowler\Connection;
use Vinelab\Bowler\Console\Commands\BowlerCommand;

class BowlerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton('vinelab.bowler.registrator', function ()
         {
            return new RegisterQueues();
        });

        $this->app->bind(Connection::class, function ()
         {
            return new Connection();
        });

        $command = new BowlerCommand();
        $this->app['vinelab.bowler.consume'] = $this->app->share(function ($app) {
            $command->setName('bowler:consume');
            return $command;
        });
        $this->commands($this->commands);

    }

}
