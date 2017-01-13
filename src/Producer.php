<?php

namespace Vinelab\Bowler;

use PhpAmqpLib\Message\AMQPMessage;
use Vinelab\Bowler\Traits\HelperTrait;
use Vinelab\Bowler\Traits\DeadLetteringTrait;
use Vinelab\Bowler\Exceptions\DeclarationMismatchException;

/**
 * Bowler Producer
 *
 * @author Ali Issa <ali@vinelab.com>
 */
class Producer
{
    use HelperTrait;
    use DeadLetteringTrait;

	/**
	 * The main class of the package where we define the channel and the connection
	 *
	 * @var Vinelab\Bowler\Connection
	 */
	private $connection;

	/**
     * The name of the queue bound to the exchange where the producer sends its messages.
     *
     * @var string
     */
    private $queueName;

	/**
	 * The name of the exchange where the producer sends its messages to
	 *
	 * @var string
	 */
	private $exchangeName;

    /**
     * The routing key used by the exchange to route messages to bounded queues.
     *
     * @var string
     */
    private $routingKey;

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
	 *
	 * @var boolean
	 */
	private $passive;

	/**
	 * If set when creating a new exchange, the exchange will be marked as durable. Durable exchanges remain active when a server restarts. Non-durable exchanges (transient exchanges) are purged if/when a server restarts.
	 *
	 * @var boolean
	 */
	private $durable;

	/**
	 * If set, the exchange is deleted when all queues have finished using it.
	 *
	 * @var boolean
	 */
	private $autoDelete;

    /**
     * Non-persistent (1) or persistent (2).
     *
     * @var [type]
     */
    private $deliveryMode;

    /**
     * The arguments that should be added to the `queue_declare` statement for dead lettering
     *
     * @var array
     */
    private $arguments = [];

	/**
	 *
	 * @param Vinelab\Bowler\Connection  $connection
	 * @param string  	$queueName
	 * @param string  	$exchangeName
	 * @param string  	$exchangeType
	 * @param string 	$routingKey
	 * @param boolean 	$passive
	 * @param boolean 	$durable
	 * @param boolean 	$autoDelete
	 * @param integer 	$deliveryMode
	 */
	public function __construct(Connection $connection, $queueName, $exchangeType = 'fanout', $exchangeName = null, $routingKey = null, $passive = false, $durable = true, $autoDelete = false, $deliveryMode = 2)
	{
		$this->connection = $connection;
		$this->queueName = $queueName;
        $this->exchangeType = $exchangeType;
        if(!$exchangeName) {
            $this->exchangeName = $queueName;
        } else {
            $this->exchangeName = $exchangeName;
        }
		$this->routingKey = $routingKey;
		$this->passive = $passive;
		$this->durable = $durable;
		$this->autoDelete = $autoDelete;
		$this->deliveryMode = $deliveryMode;
	}

	/**
	 * Publish a message to a specified exchange
	 *
	 * @param  string $data
	 *
	 * @return void
	 */
    public function publish($data)
    {
    	$channel = $this->connection->getChannel();

        try {
            $channel->exchange_declare($this->exchangeName, $this->exchangeType, $this->passive, $this->durable, $this->autoDelete);

            // The exchange corresponding queue is declared here because we cannot afford loosing any messages if there were no consumers already running. By doing this we make sure that there always be a queue bound to this exchange. This results in an anti-pattern where the producer knows about the consumer identity.
            // If loosing some messasges while the consumer is up and runing is no issue, we could disregard the queue decralation and binding to this exchange, and leave as the consumer's responsability.
            $channel->queue_declare($this->queueName, $this->passive, $this->durable, false, $this->autoDelete, false, $this->arguments);
        } catch (\Exception $e) {
            throw new DeclarationMismatchException($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e->getTrace(), $e->getPrevious(), $e->getTraceAsString(), $this->compileParameters(), $this->arguments
                    );
        }

        $msg = new AMQPMessage($data, ['delivery_mode' => $this->deliveryMode]);

        $channel->queue_bind($this->queueName, $this->exchangeName, $this->routingKey);
        $channel->basic_publish($msg, $this->exchangeName, $this->routingKey);

        echo " [x] Data Package Sent to ", $this->exchangeName, " Exchange!", "\n";
    }
}
