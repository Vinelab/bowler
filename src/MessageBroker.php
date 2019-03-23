<?php

namespace Vinelab\Bowler;

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
     * MessageBroker constructor.
     * @param AMQPMessage $message
     */
    public function __construct(AMQPMessage $message)
    {
        $this->message = $message;
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
        $this->message->delivery_info['channel']->basic_ack($this->message->delivery_info['delivery_tag'], 0);
    }

    /**
     * Negatively acknowledge a message.
     *
     * @param bool $multiple
     * @param bool $requeue
     */
    public function nackMessage($multiple = false, $requeue = false)
    {
        $this->message->delivery_info['channel']->basic_nack($this->message->delivery_info['delivery_tag'], $multiple, $requeue);
    }

    /**
     * Reject a message.
     *
     * @param bool $requeue
     */
    public function rejectMessage($requeue = false)
    {
        $this->message->delivery_info['channel']->basic_reject($this->message->delivery_info['delivery_tag'], $requeue);
    }
}
