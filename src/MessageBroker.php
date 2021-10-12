<?php

namespace Vinelab\Bowler;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * The message broker gives the user a set functionalities to perform action on queue.
 * It wrapps the PhpAmqpLib\Message\AMQPMessage.
 *
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class MessageBroker
{
    /**
     * The received message.
     *
     * @var AMQPMessage
     */
    protected $message;

    /**
     * Bowler's lifecycle manager
     *
     * @var MessageLifecycleManager
     */
    protected $lifecycle;

    /**
     * @var string
     */
    protected $queueName;

    /**
     * @var string
     */
    protected $handlerClass;

    /**
     * MessageBroker constructor.
     *
     * @param  AMQPMessage  $message
     * @param  string  $queueName
     * @param  string  $handlerClass
     * @param  MessageLifecycleManager  $lifecycle
     */
    public function __construct(
        AMQPMessage $message,
        MessageLifecycleManager $lifecycle,
        string $queueName,
        string $handlerClass
    ) {
        $this->message = $message;
        $this->lifecycle = $lifecycle;
        $this->queueName = $queueName;
        $this->handlerClass = $handlerClass;
    }

    /**
     * Get message.
     *
     * @return AMQPMessage
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Acknowledge a message.
     */
    public function ackMessage()
    {
        $this->getChannel()->basic_ack($this->message->delivery_info['delivery_tag'], false);
        $this->triggerConsumed(new Ack(Ack::MODE_ACK, false, false));
    }

    /**
     * Negatively acknowledge a message.
     *
     * @param  bool  $multiple
     * @param  bool  $requeue
     */
    public function nackMessage($multiple = false, $requeue = false)
    {
        $this->getChannel()->basic_nack($this->message->delivery_info['delivery_tag'], $multiple, $requeue);
        $this->triggerConsumed(new Ack(Ack::MODE_NACK, $requeue, $multiple));
    }

    /**
     * Reject a message.
     *
     * @param  bool  $requeue
     */
    public function rejectMessage($requeue = false)
    {
        $this->getChannel()->basic_reject($this->message->delivery_info['delivery_tag'], $requeue);
        $this->triggerConsumed(new Ack(Ack::MODE_REJECT, $requeue, false));
    }

    /**
     * @return AMQPChannel
     */
    protected function getChannel(): AMQPChannel
    {
        return $this->message->delivery_info['channel'];
    }

    /**
     * @param  Ack  $ack
     */
    protected function triggerConsumed(Ack $ack)
    {
        $this->lifecycle->triggerConsumed($this->message, $this->queueName, $this->handlerClass, $ack);
    }
}
