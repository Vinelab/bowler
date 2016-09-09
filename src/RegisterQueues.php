<?php

namespace Vinelab\Bowler;

use Vinelab\Bowler\Consumer;
use Vinelab\Bowler\Connection;

class RegisterQueues
{
	private $handlers = [];
	//private $consumer

	// public function __construct(Consumer $consumer, Connection $connection)
	// {
	// 	$this->consumer = $consumer($connection);
	// }

	public function queue($queue, $handler, $options = [])
	{
		$bowler = new Bowler();
        $bowlerConsumer = new Consumer($bowler, $queue);

        // instance
        //$handler = new App\Messaging\Handler();
        $bowlerConsumer->listenToQueue($handler);
		//$handlers->push($handler);
	}

	public function getHandlers()
	{
		return $handlers;
	}

}