<?php

namespace Vinelab\Bowler;

use Illuminate\Support\ServiceProvider;
use Vinelab\Bowler\Connection;
use Vinelab\Bowler\Console\Commands\BowlerCommand;
use Vinelab\Bowler\RegisterQueues;
use Artisan;

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

        // $command = new BowlerCommand();
        // $this->app['vinelab.bowler.consume'] = $this->app->share(function ($app) {
        //     $command->setName('bowler:consume');
        //     return $command;
        // });
        // $this->commands($this->commands);
        //
        //$kernel = new $this->app->make(Kernel::class);
        //$command = 'Vinelab\Bowler\Console\Commands\BowlerCommand';
        Artisan::add(new BowlerCommand(new RegisterQueues()));

    }

}
