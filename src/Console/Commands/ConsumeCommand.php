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
                            {--xN|exchangeName= : The exchange NAME. Defaults to queueName}
                            {--xT|exchangeType=fanout : The exchange TYPE. Supported exchanges: fanout, direct, topic. Defaults to fanout}
                            {--bK|bindingKeys=* : The consumer\'s BINDING KEYS (array)}
                            {--P|passive=0 : If set, the server will reply with Declare-Ok if the exchange and queue already exists with the same name, and raise an error if not. Defaults to 0}
                            {--D|durable=1 : Mark exchange and queue as DURABLE. Defaults to 1}
                            {--aD|autoDelete=0 : Set exchange and queue to AUTO DELETE when all queues and consumers, respectively have finished using it. Defaults to 0}
                            {--dM|deliveryMode=2 : The message DELIVERY MODE. Non-persistent 1 or persistent 2. Defaults to 2}
                            {--dlqN|deadLetterQueueName= : The dead letter queue NAME. Defaults to deadLetterExchangeName}
                            {--dlxN|deadLetterExchangeName= : The dead letter exchange NAME. Defaults to deadLetterQueueName}
                            {--dlxT|deadLetterExchangeType=fanout : The dead letter exchange TYPE. Supported exchanges: fanout, direct, topic. Defaults to fanout}
                            {--dlrK|deadLetterRoutingKey= : The dead letter ROUTING KEY}
                            {--dlmT|messageTtl= : The dead letter MESSAGE TTL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register a consumer to its queue';

    /**
     * Run the command.
     *
     * @return void.
     */
    public function handle()
    {
        $queueName = $this->argument('queueName');

        $exchangeName = ($name = $this->option('exchangeName')) ? $name : $queueName; // If the exchange name has not been set, use the queue name
        $exchangeType = $this->option('exchangeType');
        $bindingKeys = ($keys = $this->option('bindingKeys')) ? (array) $keys : [null]; // If no bidingKeys are specified push a value of null so that we can still perform the loop
        $passive = (bool) $this->option('passive');
        $durable = (bool) $this->option('durable');
        $autoDelete = (bool) $this->option('autoDelete');
        $deliveryMode = (int) $this->option('deliveryMode');

        // Dead Lettering
        $deadLetterQueueName = ($qName = $this->option('deadLetterQueueName')) ? $qName : (($xName = $this->option('deadLetterExchangeName')) ? $xName : null);
        $deadLetterExchangeName = ($xName = $this->option('deadLetterExchangeName')) ? $xName : (($qName = $this->option('deadLetterQueueName')) ? $qName : null);
        $deadLetterExchangeType = $this->option('deadLetterExchangeType');
        $deadLetterRoutingKey = $this->option('deadLetterRoutingKey');
        $messageTtl = (int) $this->option('messageTtl');

        require(app_path().'/Messaging/queues.php');
        $handlers = Registrator::getHandlers();

        foreach ($handlers as $handler) {
          if ($handler->queueName == $queueName) {
            $bowlerConsumer = new Consumer(app(Connection::class), $handler->queueName, $exchangeName, $exchangeType, $bindingKeys, $passive, $durable, $autoDelete, $deliveryMode);
            if($deadLetterQueueName) {
                $bowlerConsumer->configureDeadLettering($deadLetterQueueName, $deadLetterExchangeName, $deadLetterExchangeType, $deadLetterRoutingKey, $messageTtl);
            }
            $bowlerConsumer->listenToQueue($handler->className, app(ExceptionHandler::class));
          }
        }

    }
}
