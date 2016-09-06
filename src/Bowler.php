<?php

namespace Vinelab\Bowler;

define('__ROOT__', dirname(dirname(dirname(__FILE__))));
//require_once(__ROOT__.'/vendor/autoload.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Bowler
 *
 * @author Ali Issa <ali@vinelab.com>
 */
class Bowler
{
    /**
     * $connection var
     * @var string
     */
	private $connection;

    /**
     * $channel var
     * @var string
     */
    private $channel;

    /**
     *
     * @param string  $machine the ip of the rabbitmq server, default: localhost
     * @param integer $port. default: 5672
     * @param string  $username, default: guest
     * @param string  $password, default: guest
     */
    public function __construct($machine = 'localhost', $port = 5672, $username = 'guest', $password = 'guest')
    {
        $this->connection = new AMQPStreamConnection($machine, $port, $username, $password);
        $this->channel = $this->connection->channel();
    }

    public function getConnection()
    {
    	return $this->connection;
    }

	public function getChannel()
    {
    	return $this->channel;
    }

    public function __desctruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
