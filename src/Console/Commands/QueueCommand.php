<?php

namespace Vinelab\Bowler\Console\Commands;

use Vinelab\Bowler\Consumer;
use Vinelab\Bowler\Connection;
use Illuminate\Console\Command;
use Vinelab\Bowler\RegisterQueues;
use Vinelab\Bowler\Facades\Registrator;
use Vinelab\Bowler\Generators\QueueGenerator;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class QueueCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'bowler:queue {queue} {handler}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add queue and generate corresponding message handler';

    /**
     * Run the command.
     *
     * @return void.
     */
    public function handle()
    {
        $queueGenerator = new QueueGenerator();

        $queue = $this->argument('queue');
        $handler = studly_case($this->argument('handler')).'MessageHandler';

        try {
            $queueGenerator->generate($queue, $handler);

            $this->info(
                'Queue '.$queue.' added successfully.'.
                "\n".
                'Handler class '.$handler.' created successfully.'.
                "\n"
            );
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function getArguments()
    {
        return [
            ['queue', InputArgument::REQUIRED, 'The queue\'s name.'],
            ['handler', InputArgument::REQUIRED, 'The handler assigned to queue.'],
        ];
    }
}
