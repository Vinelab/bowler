<?php

namespace Vinelab\Bowler;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Bowler Producer
 *
 * @author Ali Issa <ali@vinelab.com>
 */
class Producer
{

	/**
	 * the main class of the package where we define the channel and the connection
	 *
	 * @var Vinelab\Bowler\Connection
	 */
	private $connection;

	/**
	 * the name of the exchange where the producer sends its messages to
	 * @var string
	 */
	private $exchangeName;

	/**
	 * type of exchange:
	 * fanout: routes messages to all of the queues that are bound to it and the routing key is ignored
	 *
	 * direct: delivers messages to queues based on the message routing key. A direct exchange is ideal for the unicast routing of messages (although they can be used for multicast routing as well)
	 *
	 * default: a direct exchange with no name (empty string) pre-declared by the broker. It has one special property that makes it very useful for simple applications: every queue that is created is automatically bound to it with a routing key which is the same as the queue name
	 *
	 * topic: route messages to one or many queues based on matching between a message routing key and the pattern that was used to bind a queue to an exchange. The topic exchange type is often used to implement various publish/subscribe pattern variations. Topic exchanges are commonly used for the multicast routing of messages
	 *
	 * @var string
	 */
	private $exchangeType;

	/**
	 * If set, the server will reply with Declare-Ok if the exchange already exists with the same name, and raise an error if not. The client can use this to check whether an exchange exists without modifying the server state.
	 * @var boolean
	 */
	private $passive;

	/**
	 * If set when creating a new exchange, the exchange will be marked as durable. Durable exchanges remain active when a server restarts. Non-durable exchanges (transient exchanges) are purged if/when a server restarts.
	 * @var boolean
	 */
	private $durable;

	/**
	 * If set, the exchange is deleted when all queues have finished using it.
	 * @var boolean
	 */
	private $autoDelete;

	/**
	 * Non-persistent (1) or persistent (2).
	 * @var [type]
	 */
	private $deliveryMode;

	/**
	 *
	 * @param Vinelab\Bowler\Connection  $connection
	 * @param string  $exchangeName
	 * @param string  $exchangeType
	 * @param boolean $passive
	 * @param boolean $durable
	 * @param boolean $autoDelete
	 * @param integer $deliveryMode
	 */
	public function __construct(Connection $connection, $exchangeName, $exchangeType, $passive = false, $durable = true, $autoDelete = false, $deliveryMode = 2)
	{
		$this->connection = $connection;
		$this->exchangeName = $exchangeName;
		$this->exchangeType = $exchangeType;
		$this->passive = $passive;
		$this->durable = $durable;
		$this->autoDelete = $autoDelete;
		$this->deliveryMode = $deliveryMode;
	}

	/**
	 * publish a message to a specified exchange
	 * @param  string $data
	 * @param  string $route: the route where the message should be published to
	 * @return void
	 */
    public function publish($data)
    {
        $this->connection->getChannel()->exchange_declare($this->exchangeName, $this->exchangeType, $this->passive, $this->durable, $this->autoDelete);

        list($queue_name) = $this->connection->getChannel()->queue_declare($this->exchangeName, $this->passive, $this->durable, false, $this->autoDelete);
        $this->connection->getChannel()->queue_bind($queue_name, $this->exchangeName);

        $msg = new AMQPMessage($data, ['delivery_mode' => $this->deliveryMode]);
        $this->connection->getChannel()->basic_publish($msg, '', $this->exchangeName);
        echo " [x] Data Package Sent to CRUD Exchange!'\n";
    }
}
