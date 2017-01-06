<?php

namespace Vinelab\Bowler;

use PhpAmqpLib\Message\AMQPMessage;
use Vinelab\Bowler\Contracts\BowlerExceptionHandler as ExceptionHandler;

/**
 * Bowler Consumer.
 *
 * @author Ali Issa <ali@vinelab.com>
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class Consumer
{
    /**
     * the main class of the package where we define the channel and the connection.
     *
     * @var Vinelab\Bowler\Connection
     */
    private $connection;

    /**
     * the name of the exchange where the producer sends its messages to.
     * The name of the queue bound to the exchange where the producer sends its messages.
     *
     * @var string
     */
    private $queueName;
     *
     * @var string
     */
    private $exchangeName;

    /**
     * The binding keys used by the exchange to route messages to bounded queues.
     *
     * @var string
     */
    private $bindingKeys;

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
    private $exchangeType;

    /**
     * If set, the server will reply with Declare-Ok if the exchange already exists with the same name, and raise an error if not. The client can use this to check whether an exchange exists without modifying the server state.
     *
     * @var bool
     */
    private $passive;

    /**
     * If set when creating a new exchange, the exchange will be marked as durable. Durable exchanges remain active when a server restarts. Non-durable exchanges (transient exchanges) are purged if/when a server restarts.
     *
     * @var bool
     */
    private $durable;

    /**
     * If set, the exchange is deleted when all queues have finished using it.
     *
     * @var bool
     */
    private $autoDelete;

    /**
     * Non-persistent (1) or persistent (2).
     *
     * @var [type]
     */
    private $deliveryMode;

    private $msgProcessor;

    /**
     * @param Vinelab\Bowler\Connection $connection
     * @param string                $queueName
     * @param string                $exchangeName
     * @param string                $exchangeType
     * @param array                 $bindingKeys
     * @param bool                  $passive
     * @param bool                  $durable
     * @param bool                  $autoDelete
     * @param int                   $deliveryMode
     */
    public function __construct(Connection $connection, $queueName, $exchangeName, $exchangeType, $bindingKeys, $passive = false, $durable = true, $autoDelete = false, $deliveryMode = 2)
    {
        $this->connection = $connection;
        $this->queueName = $queueName;
        $this->exchangeName = $exchangeName;
        $this->exchangeType = $exchangeType;
        $this->bindingKeys = $bindingKeys;
        $this->passive = $passive;
        $this->durable = $durable;
        $this->autoDelete = $autoDelete;
        $this->deliveryMode = $deliveryMode;
    }

    /**
     * consume a message from a specified exchange.
     *
     * @param string $data
     */
    public function listenToQueue($handlerClass, ExceptionHandler $exceptionHandler)
    {

        echo ' [*] Listening to Queue: ' . $this->exchangeName . ' To exit press CTRL+C', "\n";
        $channel->exchange_declare($this->exchangeName, $this->exchangeType, $this->passive, $this->durable, $this->autoDelete);
        $channel->queue_declare($this->queueName, $this->passive, $this->durable, false, $this->autoDelete);

        foreach ($this->bindingKeys as $bindingKey) {
            $channel->queue_bind($this->queueName, $this->exchangeName, $bindingKey);
        }

        $handler = new $handlerClass;

        if(method_exists($handler, 'setConsumer')) {
            $handler->setConsumer($this);
        }

        $callback = function ($msg) use ($handler, $exceptionHandler) {
            try {
                $handler->handle($msg);
                $this->ackMessage($msg);
            } catch(\Exception $e) {
                $exceptionHandler->reportQueue($e, $msg);
                $exceptionHandler->renderQueue($e, $msg);

                if(method_exists($handler, 'handleError')) {
                    $handler->handleError($e, $msg);
                }
            }
        };

        $this->connection->getChannel()->basic_qos(null, 1, null);
        $channel->basic_consume($this->queueName, '', false, false, false, false, $callback);

        while (count($this->connection->getChannel()->callbacks)) {
            $this->connection->getChannel()->wait();
        }
    }

    /**
     * acknowledge a messasge.
     *
     * @param PhpAmqpLib\Message\AMQPMessage $msg
     */
    public function ackMessage($msg)
    {
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    }

    /**
     * reject a messasge.
     *
     * @param PhpAmqpLib\Message\AMQPMessage $msg
     */
    public function rejectMessage($msg)
    {
        $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
    }
}
