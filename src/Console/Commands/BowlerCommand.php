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

    public function __construct(RegisterQueues $registerQueues)
    {
        parent::__construct();

        $this->registerQueues = $registerQueues;
    }


    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'bowler:consume {queue}';

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
        $queueName = $this->argument('queue');

        require(app_path().'/Messaging/queues.php');
        $handlers = Registrator::getHandlers();

        foreach ($handlers as $handler) {
          if ($handler->queueName == $queueName) {
            $bowlerConsumer = new Consumer(app(Connection::class), $handler->queueName);
            $bowlerConsumer->listenToQueue($handler->className);
          }
        }

    }

}
