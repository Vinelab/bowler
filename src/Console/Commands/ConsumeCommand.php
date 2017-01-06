<?php

namespace Vinelab\Bowler\Console\Commands;

use Vinelab\Bowler\Consumer;
use Vinelab\Bowler\Connection;
use Illuminate\Console\Command;
use Vinelab\Bowler\RegisterQueues;
use Vinelab\Bowler\Facades\Registrator;
use Vinelab\Bowler\Contracts\BowlerExceptionHandler as ExceptionHandler;

/**
 * @author Ali Issa <ali@vinelab.com>
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class ConsumeCommand extends Command
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
    protected $signature = 'bowler:consume
                            {queueName : The queue NAME}
                            {--exchangeName= : The exchange NAME. If not specified the queue name will be used}
                            {--exchangeType=fanout : The exchange TYPE. Supported exchanges: fanout, direct, topic}
                            {--bindingKeys=* : The consumer\'s BINDINGKEYS (array)}
                            {--passive=0 : }
                            {--durable=1 : Mark exchange and queue as DURABLE}
                            {--autoDelete=0 : Set exchange and queue to AUTODELETE when all queues and consumers, respectively have finished using it}
                            {--deliveryMode=2 : The message DELIVERYMODE. Non-persistent 1 or persistent 2}';

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
        $queueName = $this->argument('queueName');

        // If the exchange name has not been set, use the queue name
        $exchangeName = ($name = $this->option('exchangeName')) ? $name : $queueName;
        $exchangeType = $this->option('exchangeType');

        // If no bidingKeys are specified push a value of null so that we can still perform the loop
        $bindingKeys = ($keys = $this->option('bindingKeys')) ? (array) $keys : [null];
        $passive = (bool) $this->option('passive');
        $durable = (bool) $this->option('durable');
        $autoDelete = (bool) $this->option('autoDelete');
        $deliveryMode = (int) $this->option('deliveryMode');

        require(app_path().'/Messaging/queues.php');
        $handlers = Registrator::getHandlers();

        foreach ($handlers as $handler) {
          if ($handler->queueName == $queueName) {
            $bowlerConsumer = new Consumer(app(Connection::class), $handler->queueName, $exchangeName, $exchangeType, $bindingKeys, $passive, $durable, $autoDelete, $deliveryMode);
            $bowlerConsumer->listenToQueue($handler->className, app(ExceptionHandler::class));
          }
        }

    }

}
