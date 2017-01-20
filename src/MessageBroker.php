<?php

namespace Vinelab\Bowler;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * The message broker gives the user a set functionalities to perform action on queue.
 * It wrapps the PhpAmqpLib\Message\AMQPMessage $msg and add
 *
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class MessageBroker
{
    /**
     * The received message.
     *
     * @var PhpAmqpLib\Message\AMQPMessage
     */
    protected $msg;

    public function __construct(AMQPMessage $msg)
    {
        $this->msg = $msg;
    }

    /**
     * Get message.
     *
     * @return PhpAmqpLib\Message\AMQPMessage
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
        $this->msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag'], 0);
    }

    /**
     * Negatively acknowledge a message.
     *
     * @param bool  $multiple
     * @param bool  $requeue
     */
    public function nackMessage($multiple = false, $requeue = false)
    {
        $this->msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], $multiple, $requeue);
    }

    /**
     * Reject a message.
     *
     * @param bool  $requeue
     */
    public function rejectMessage($requeue = false)
    {
        $this->msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], $requeue);
    }
}
