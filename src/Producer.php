<?php

namespace Vinelab\Bowler;

use PhpAmqpLib\Message\AMQPMessage;
use Vinelab\Bowler\Traits\CompileParametersTrait;
use Vinelab\Bowler\Exceptions\Handler as BowlerExceptionHandler;

/**
 * Bowler Producer.
 *
 * @author Ali Issa <ali@vinelab.com>
 */
class Producer
{
    use CompileParametersTrait;

    /**
     * The main class of the package where we define the channel and the connection.
     *
     * @var Vinelab\Bowler\Connection
     */
    protected $connection;

    /**
     * The name of the exchange where the producer sends its messages to.
     *
     * @var string
     */
    protected $exchangeName;

    /**
     * type of exchange:
     * fanout: routes messages to all of the queues that are bound to it and the routing key is ignored.
     *
     * direct: delivers messages to queues based on the message routing key. A direct exchange is ideal for the unicast routing of messages (although they can be used for multicast routing as well)
     *
     * default: a direct exchange with no name (empty string) pre-declared by the broker. It has one special property that makes it very useful for simple applications: every queue that is created is automatically bound to it with a routing key which is the same as the queue name
     *
     * topic: route messages to one or many queues based on matching between a message routing key and the pattern that was used to bind a queue to an exchange. The topic exchange type is often used to implement various publish/subscribe pattern variations. Topic exchanges are commonly used for the multicast routing of messages
     *
     * @var string
     */
    protected $exchangeType;

    /**
     * If set, the server will reply with Declare-Ok if the exchange already exists with the same name, and raise an error if not. The client can use this to check whether an exchange exists without modifying the server state.
     *
     * @var bool
     */
    protected $passive;

    /**
     * If set when creating a new exchange, the exchange will be marked as durable. Durable exchanges remain active when a server restarts. Non-durable exchanges (transient exchanges) are purged if/when a server restarts.
     *
     * @var bool
     */
    protected $durable;

    /**
     * If set, the exchange is deleted when all queues have finished using it.
     *
     * @var bool
     */
    protected $autoDelete;

    /**
     * Non-persistent (1) or persistent (2).
     *
     * @var [type]
     */
    protected $deliveryMode;

    /**
     * @param Vinelab\Bowler\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Setup Producer.
     *
     * @param string $exchangeName
     * @param string $exchangeType
     * @param bool   $passive
     * @param bool   $durable
     * @param bool   $autoDelete
     * @param int    $deliveryMode
     */
    public function setup($exchangeName, $exchangeType = 'fanout', $passive = false, $durable = true, $autoDelete = false, $deliveryMode = 2)
    {
        $this->exchangeName = $exchangeName;
        $this->exchangeType = $exchangeType;
        $this->passive = $passive;
        $this->durable = $durable;
        $this->autoDelete = $autoDelete;
        $this->deliveryMode = $deliveryMode;
    }

    /**
     * Send a message to a specified exchange.
     *
     * @param string $data
     * @param string $routingKey    The routing key used by the exchange to route messages to bounded queues.
     */
    public function send($data = null, $routingKey = null)
    {
        $channel = $this->connection->getChannel();

        try {
            $channel->exchange_declare($this->exchangeName, $this->exchangeType, $this->passive, $this->durable, $this->autoDelete);
        } catch (\Exception $e) {
            app(BowlerExceptionHandler::class)->handleServerException($e, $this->compileParameters());
        }

        $msg = new AMQPMessage($data, ['delivery_mode' => $this->deliveryMode]);

        $channel->basic_publish($msg, $this->exchangeName, $routingKey);

        echo ' [x] Data Package Sent to ', $this->exchangeName, ' Exchange!', "\n";
    }
}
