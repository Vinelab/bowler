<?php

namespace Vinelab\Bowler\Console\Commands;

use Vinelab\Bowler\RegisterQueues;
use Vinelab\Bowler\Consumer;
use Vinelab\Bowler\Connection;

//use Illuminate\Console\Command;
use App\Commands\Command;

/**
 * @author Ali Issa <ali@vinelab.com>
 */
class BowlerCommand extends Command implements SelfHandling
{
    protected $registerQueues;
    //protected $consumer;

    public function __construct(RegisterQueues $registerQueues)
    {
        parent::__construct();

        $this->registerQueues = $registerQueues;
        //$this->$consumer = $consumer;
    }


    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'consume';

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
        //     $consumer = new Consumer($conn, $handler->)
        // }
        echo 'bowler command executed';
    }

}