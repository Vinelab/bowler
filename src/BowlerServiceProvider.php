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

        return $this->app->make(Connection::class);

        $this->app->singleton('vinelab.bowler.registrator', function ()
         {
            return new RegisterQueues();
        });

        $this->app->singleton('vinelab.bowler', 'Vinelab\Bowler');
        $this->app['vinelab.bowler.consume'] = $this->app->share(function ($app) {
            $command = new BowlerCommand();
            $command->setName('bowler:consume');
            return $command;
        });
    }

}
