<?php

namespace Vinelab\Bowler;

define('__ROOT__', dirname(dirname(dirname(__FILE__))));
//require_once(__ROOT__.'/vendor/autoload.php');

use Vinelab\Http\Client as HTTPClient;
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
     * RabbitMQ server host.
     *
     * @var string
     */
    private $host = 'localhost';

    /**
     * Management plugin's port.
     *
     * @var int
     */
    private $managementPort = 15672;

    /**
     * RabbitMQ server username.
     *
     * @var string
     */
    private $username = 'guest';

    /**
     * RabbitMQ server password.
     *
     * @var string
     */
    private $password = 'guest';

    /**
     * RabbitMQ connection timeout.
     *
     * @var int
     */
    private $connectionTimeout = 30;

    /**
     * RabbitMQ read/write timeout.
     *
     * @var int
     */
    private $readWriteTimeout = 30;

    /**
     * RabbitMQ heartbeat frequency.
     *
     * @var int
     */
    private $heartbeat = 15;

    /**
     * @param string $host      the ip of the rabbitmq server, default: localhost
     * @param int    $port.     default: 5672
     * @param string $username, default: guest
     * @param string $password, default: guest
     * @param int    $connectionTimeout, default: 30
     * @param int    $readWriteTimeout, default: 30
     * @param int    $heartbeat, default: 15
     */
    public function __construct($host = 'localhost', $port = 5672, $username = 'guest', $password = 'guest', $connectionTimeout = 30, $readWriteTimeout = 30, $heartbeat = 15)
    {
        $this->host = $host;
        $this->poart = $port;
        $this->username = $username;
        $this->password = $password;
        $this->connectionTimeout = $connectionTimeout;
        $this->readWriteTimeout = $readWriteTimeout;
        $this->heartbeat = $heartbeat;

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
            $connectionTimeout,
            $readWriteTimeout,
            $context = null,
            $keepalive = false,
            $heartbeat
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

    /**
     * Fetch the list of consumers details for the given queue name using the management API.
     *
     * @param  string $queueName
     * @param  string $columns
     *
     * @return array
     */
    public function fetchQueueConsumers($queueName, string $columns = 'consumer_details.consumer_tag')
    {
        $http = app(HTTPClient::class);

        $request = [
            'url' => $this->host.':'.$this->managementPort.'/api/queues/%2F/'.$queueName,
            'params' => ['columns' => $columns],
            'auth' => [
                'username' => $this->username,
                'password' => $this->password,
            ],
        ];

        return $http->get($request)->json();
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
