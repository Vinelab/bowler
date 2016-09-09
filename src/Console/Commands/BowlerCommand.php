<?php

namespace Vinelab\Bowler\Console\Commands;

use Vinelab\Bowler\RegisterQueues;
use Vinelab\Bowler\Consumer;
use Vinelab\Bowler\Connection;
use Vinelab\Bowler\Facades\Registrator;

use Illuminate\Console\Command;

/**
 * @author Ali Issa <ali@vinelab.com>
 */
class BowlerCommand extends Command
{
    protected $registerQueues;
    protected $connction;

    public function __construct(RegisterQueues $registerQueues, Connection $connection)
    {
        parent::__construct();

        $this->registerQueues = $registerQueues;
        $this->connection = $connection;
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
        require(app_path().'/Messaging/queues.php');
        $handlers = Registrator::getHandlers();

        foreach ($handlers as $handler) {
            $bowlerConsumer = new Consumer($this->connection, $handler->queueName);
            $bowlerConsumer->listenToQueue($handler->className);
        }

    }

}
