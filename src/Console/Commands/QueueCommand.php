<?php

namespace Vinelab\Bowler\Console\Commands;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Vinelab\Bowler\Generators\HandlerGenerator;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class QueueCommand extends Command
{
    const TYPE = 'queue';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'bowler:make:queue
                            {queueName : The queue NAME}
                            {handler : The handler class NAME}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Register a queue and generate it's message handler";

    /**
     * Run the command.
     */
    public function handle()
    {
        $handlerGenerator = new HandlerGenerator();

        $queue = $this->argument('queueName');
        $handler = Str::studly(preg_replace('/Handler(\.php)?$/', '', $this->argument('handler')).'Handler');

        try {
            $handlerGenerator->generate($queue, $handler, self::TYPE);

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

    /**
     * @return array
     */
    public function getArguments()
    {
        return [
            ['queueName', InputArgument::REQUIRED, 'The queue\'s name.'],
            ['handler', InputArgument::REQUIRED, 'The handler assigned to queue.'],
        ];
    }
}
