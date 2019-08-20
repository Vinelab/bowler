<?php

namespace Vinelab\Bowler;

define('__ROOT__', dirname(dirname(dirname(__FILE__))));
//require_once(__ROOT__.'/vendor/autoload.php');

use PhpAmqpLib\Channel\AMQPChannel;
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
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * $channel var.
     *
     * @var AMQPChannel
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
     * RabbitMQ vhost.
     * @var string
     */
    private $vhost = '/';

    /**
     * @param string $host      the ip of the rabbitmq server, default: localhost
     * @param int    $port.     default: 5672
     * @param string $username, default: guest
     * @param string $password, default: guest
     * @param int    $connectionTimeout, default: 30
     * @param int    $readWriteTimeout, default: 30
     * @param int    $heartbeat, default: 15
     */
    public function __construct($host = 'localhost', $port = 5672, $username = 'guest', $password = 'guest', $connectionTimeout = 30, $readWriteTimeout = 30, $heartbeat = 15, $vhost = '/')
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->connectionTimeout = $connectionTimeout;
        $this->readWriteTimeout = $readWriteTimeout;
        $this->heartbeat = $heartbeat;
        $this->vhost = $vhost;

        $this->initAMQPStreamConnection($host, $port, $username, $password, $connectionTimeout, $readWriteTimeout, $heartbeat, $vhost);
    }

    protected function initAMQPStreamConnection($host, $port, $username, $password, $connectionTimeout, $readWriteTimeout, $heartbeat, $vhost = '/', $insist = false, $login_method = 'AMQPLAIN', $login_response = null, $locale = 'en_US', $context = null, $keepalive = false)
    {
        $insist = false;
        $login_method = 'AMQPLAIN';
        $login_response = null;
        $locale = 'en_US';
        $context = null;
        $keepalive = false;

        $this->connection = app()->makeWith(AMQPStreamConnection::class, [
            'host' => $host,
            'port' => $port,
            'user' => $username,
            'password' => $password,
            'vhost' => $vhost,
            'insist' => $insist,
            'login_method' => $login_method,
            'login_response' => $login_response,
            'locale' => $locale,
            'connection_timeout' => $connectionTimeout,
            'read_write_timeout' => $readWriteTimeout,
            'context' => $context,
            'keepalive' => $keepalive,
            'heartbeat' => $heartbeat,
        ]);

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
