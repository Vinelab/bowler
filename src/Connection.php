<?php

namespace Vinelab\Bowler;

define('__ROOT__', dirname(dirname(dirname(__FILE__))));
//require_once(__ROOT__.'/vendor/autoload.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Connection.
 *
 * @author Ali Issa <ali@vinelab.com>
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class Connection
{
    /**
     * $connection var.
     *
     * @var string
     */
    private $connection;

    /**
     * $channel var.
     *
     * @var string
     */
    private $channel;

    /**
     * @param string $host      the ip of the rabbitmq server, default: localhost
     * @param int    $port.     default: 5672
     * @param string $username, default: guest
     * @param string $password, default: guest
     */
    public function __construct($host = 'localhost', $port = 5672, $username = 'guest', $password = 'guest')
    {
        $this->connection = new AMQPStreamConnection(
            $host,
            $port,
            $username,
            $password,
            $vhost = '/',
            $insist = false,
            $login_method = 'AMQPLAIN',
            $login_response = null,
            $locale = 'en_US',
            $connection_timeout = 3,
            $read_write_timeout = 3,
            $context = null,
            $keepalive = false,
            $heartbeat = 15
        );

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

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
