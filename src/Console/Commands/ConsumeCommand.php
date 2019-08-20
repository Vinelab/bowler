<?php

namespace Vinelab\Bowler\Console\Commands;

use ErrorException;
use Illuminate\Console\Command;
use Vinelab\Bowler\Connection;
use Vinelab\Bowler\Consumer;
use Vinelab\Bowler\Exceptions\BowlerGeneralException;
use Vinelab\Bowler\Exceptions\DeclarationMismatchException;
use Vinelab\Bowler\Exceptions\Handler as BowlerExceptionHandler;
use Vinelab\Bowler\Exceptions\InvalidSetupException;
use Vinelab\Bowler\Exceptions\UnregisteredQueueException;
use Vinelab\Bowler\RegisterQueues;

/**
 * @author Ali Issa <ali@vinelab.com>
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class ConsumeCommand extends Command
{
    /**
     * @var RegisterQueues
     */
    protected $registrator;

    /**
     * ConsumeCommand constructor.
     * @param  RegisterQueues  $registrator
     */
    public function __construct(RegisterQueues $registrator)
    {
        parent::__construct();

        $this->registrator = $registrator;
    }

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'bowler:consume
                            {queueName : The queue NAME}
                            {--N|exchangeName= : The exchange NAME. Defaults to queueName}
                            {--T|exchangeType=fanout : The exchange TYPE. Supported exchanges: fanout, direct, topic. Defaults to fanout}
                            {--K|bindingKeys=* : The consumer\'s BINDING KEYS (array)}
                            {--p|passive=0 : If set, the server will reply with Declare-Ok if the exchange and queue already exists with the same name, and raise an error if not. Defaults to 0}
                            {--d|durable=1 : Mark exchange and queue as DURABLE. Defaults to 1}
                            {--D|autoDelete=0 : Set exchange and queue to AUTO DELETE when all queues and consumers, respectively have finished using it. Defaults to 0}
                            {--deadLetterQueueName= : The dead letter queue NAME. Defaults to deadLetterExchangeName}
                            {--deadLetterExchangeName= : The dead letter exchange NAME. Defaults to deadLetterQueueName}
                            {--deadLetterExchangeType=fanout : The dead letter exchange TYPE. Supported exchanges: fanout, direct, topic. Defaults to fanout}
                            {--deadLetterRoutingKey= : The dead letter ROUTING KEY}
                            {--messageTTL= : If set, specifies how long, in milliseconds, before a message is declared dead letter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a consumer queue';

    /**
     * Run the command.
     * @throws UnregisteredQueueException
     * @throws ErrorException
     * @throws BowlerGeneralException
     * @throws DeclarationMismatchException
     * @throws InvalidSetupException
     */
    public function handle()
    {
        $queueName = $this->argument('queueName');

        // Options
        $exchangeName = ($name = $this->option('exchangeName')) ? $name : $queueName; // If the exchange name has not been set, use the queue name
        $exchangeType = $this->option('exchangeType');
        $bindingKeys = (array) $this->option('bindingKeys');
        $passive = (bool) $this->option('passive');
        $durable = (bool) $this->option('durable');
        $autoDelete = (bool) $this->option('autoDelete');

        // Dead Lettering
        $deadLetterQueueName = ($dlQueueName = $this->option('deadLetterQueueName')) ? $dlQueueName : (($dlExchangeName = $this->option('deadLetterExchangeName')) ? $dlExchangeName : null);
        $deadLetterExchangeName = ($dlExchangeName = $this->option('deadLetterExchangeName')) ? $dlExchangeName : (($dlQueueName = $this->option('deadLetterQueueName')) ? $dlQueueName : null);
        $deadLetterExchangeType = $this->option('deadLetterExchangeType');
        $deadLetterRoutingKey = $this->option('deadLetterRoutingKey');
        $messageTTL = ($ttl = $this->option('messageTTL')) ? (int) $ttl : null;

        $this->loadQueuesDefinitions();
        $handlers = $this->registrator->getHandlers();

        foreach ($handlers as $handler) {
            if ($handler->queueName == $queueName) {

              // If options are set in Registrator:queue(string $queueName,string $Handler, array $options).
              if (!empty($handler->options)) {
                  // Use whatever the user has set/provided, to override our defaults.
                  extract($handler->options);
              }

                $bowlerConsumer = new Consumer(app(Connection::class), $handler->queueName, $exchangeName, $exchangeType, $bindingKeys, $passive, $durable, $autoDelete);

                if ($deadLetterQueueName) {

                    // If configured as options and deadLetterExchangeName is not specified, default to deadLetterQueueName.
                    $deadLetterExchangeName = isset($deadLetterExchangeName) ? $deadLetterExchangeName : $deadLetterQueueName;

                    $bowlerConsumer->configureDeadLettering($deadLetterQueueName, $deadLetterExchangeName, $deadLetterExchangeType, $deadLetterRoutingKey, $messageTTL);
                }

                $bowlerConsumer->listenToQueue($handler->className, app(BowlerExceptionHandler::class));
            }
        }

        throw new UnregisteredQueueException('No registered queue found with name '.$queueName.'.');
    }

    public function loadQueuesDefinitions()
    {
        $path = app_path().'/Messaging/queues.php';

        if (!file_exists($path)) {
            return $this->error('Queues definitions file not found. Please create it at '.$path);
        }

        require $path;
    }
}
