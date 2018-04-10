<?php

namespace Vinelab\Bowler\Console\Commands;

use ErrorException;
use Vinelab\Bowler\Connection;
use Illuminate\Console\Command;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;

/**
 * @author Abed Halawi <abed.halawi@vinelab.com>
 */
class ConsumerHealthCheckCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'bowler:healthcheck:consumer
                            {queueName : The queue name}
                            {--c|consumers=1 : The expected number of consumers to be connected to the queue specified by queueName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the health of connected consumers to a queue, with a minimum of 1 connection.';

    /**
     * Run the command.
     */
    public function handle()
    {
        $queueName = $this->argument('queueName');
        $expectedConsumers = (int) $this->option('consumers');

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

            // consumer count and minimum consumers connected should match
            if ($consumerCount !== $expectedConsumers) {
                $this->error('Health check failed. Minimum consumer count not met: expected '.$expectedConsumers.' got '.$consumerCount);
                return 1;
            }

            $this->info('Consumers healthy with '.$consumerCount.' live connections.');
        } catch (AMQPProtocolChannelException $e) {
            if ($e->getCode() === 404) {
                $this->error('Queue with name '.$queueName.' does not exist.');
                return 1;
            }

            $this->error('An unknown channel exception occurred');

            return 1;
        }

        return 0;
    }
}
