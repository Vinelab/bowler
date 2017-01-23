<?php

namespace Vinelab\Bowler\Console\Commands;

use Illuminate\Console\Command;
use Vinelab\Bowler\Generators\HandlerGenerator;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class SubscribeCommand extends Command
{
    const TYPE = 'subscribe';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'bowler:make:subscriber
                            {queueName : The queue NAME}
                            {handler : The handler class NAME}
                            {--E|expressive : If set, the queue name will explicitly express that it is a pub/sub queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Register a default pub/sub queue and generate it's message handler";

    /**
     * Run the command.
     */
    public function handle()
    {
        $handlerGenerator = new HandlerGenerator();

        $queue = $this->argument('queueName');

        if ($this->option('expressive')) {
            $queue = $queue.'-pub-sub';
        }

        $handler = studly_case(preg_replace('/Handler(\.php)?$/', '', $this->argument('handler')).'Handler');

        try {
            $handlerGenerator->generate($queue, $handler, self::TYPE);

            $this->info(
                'Queue '.$queue.' added successfully and bound to the default `pub-sub` exchange.'.
                "\n".
                'Handler class '.$handler.' created successfully.'.
                "\n"
            );
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
