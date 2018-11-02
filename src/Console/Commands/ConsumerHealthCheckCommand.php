<?php

namespace Vinelab\Bowler\Console\Commands;

use ErrorException;
use Illuminate\Console\Command;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use Vinelab\Bowler\Connection;
use Vinelab\Bowler\Traits\ConsumerTagTrait;

/**
 * @author Abed Halawi <abed.halawi@vinelab.com>
 */
class ConsumerHealthCheckCommand extends Command
{
    use ConsumerTagTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'bowler:healthcheck:consumer
                            {queueName : The queue name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the health of connected consumers to a given queue.';

    /**
     * Run the command.
     */
    public function handle()
    {
        $queueName = $this->argument('queueName');

        // may or may not be able to connect
        try {
            $connection = app(Connection::class);
        } catch (ErrorException $e) {
            $this->error('Unable to connect to RabbitMQ.');

            return 1;
        }

        $channel = $connection->getChannel();

        try {
            // $queue is the same as $queueName but to avoid overriding we're naming it differently.
            list($queue, $messageCount, $consumerCount) = $channel->queue_declare($queueName,
                $passive = true,
                $durable = false,
                $exclusive = false,
                $autoDelete = false,
                []
            );

            $response = $connection->fetchQueueConsumers($queueName);

            if ($response && isset($response->consumer_details) && !empty($response->consumer_details)) {
                // read consumer tag
                $tag = $this->readConsumerTag();

                // find consumer tag within the list of returned consumers
                foreach ($response->consumer_details as $consumer) {
                    if (isset($consumer->consumer_tag) && $consumer->consumer_tag == $tag) {
                        $this->info('Healthy consumer with tag '.$tag);

                        return 0;
                    }
                }

                $this->error('Health check failed! Could not find consumer with tag "'.$tag.'"');

                return 1;
            }

            $this->error('No consumers connected to queue "'.$queueName.'"');

            return 1;
        } catch (AMQPProtocolChannelException $e) {
            switch ($e->getCode()) {
                case 404:
                    $this->error('Queue with name '.$queueName.' does not exist.');
                    break;
                default:
                    $this->error('An unknown channel exception occurred.');
                    break;
            }

            return 1;
        }

        return 0;
    }
}
