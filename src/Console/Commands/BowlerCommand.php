<?php

namespace Vinelab\Bowler\Console\Commands;

use Vinelab\Bowler\RegisterQueues;
use Vinelab\Bowler\Consumer;
use Vinelab\Bowler\Connection;

use Illuminate\Console\Command;

/**
 * @author Ali Issa <ali@vinelab.com>
 */
class BowlerCommand extends Command
{
    protected $registerQueues;
    //protected $consumer;

    public function __construct(RegisterQueues $registerQueues)
    {
        parent::__construct();

        //$this->registerQueues = $registerQueues;
        //$this->$consumer = $consumer;
    }


    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'bowler:consume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'register all consumers to their queues';

    /**
     * Run the command.
     *
     * @return void.
     */
    public function handle()
    {
        // $handlers = $this->registerQueues->getHandlers();
        // $conn = new Connection();

        // foreach ($handlers as $handler) {
        //$bowler = new Bowler('localhost', 5672);
        // $bowlerConsumer = new Consumer($bowler, 'crud', 'fanout');

        // // instance
        // $handler = new App\Messaging\Handler();
        // $bowlerConsumer->listenToQueue($handler);
        // }
        //echo "bowler command executed\n";
        //
        $bowler = new Bowler();
        $bowlerConsumer = new Consumer($bowler, 'crud', 'fanout');

        // instance
        $handler = new App\Messaging\Handler();
        $bowlerConsumer->listenToQueue($handler);
    }

}