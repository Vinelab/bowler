<?php

namespace Vinelab\Bowler\Tests;

use Illuminate\Support\Arr;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Vinelab\Bowler\Ack;
use Vinelab\Bowler\Exceptions\UnrecalledAMQPMessageException;
use Vinelab\Bowler\LifecycleManager;

class LifecycleManagerTest extends TestCase
{
    /**
     * @throws UnrecalledAMQPMessageException
     */
    public function test_before_publish()
    {
        $exec = false;

        $lifecycle = new LifecycleManager();
        $lifecycle->beforePublish(function (AMQPMessage $msg, $exchangeName, $routingKey) use (&$exec) {
            $exec = true;

            $this->assertEquals('example', $msg->body);
            $this->assertEquals('logs', $exchangeName);
            $this->assertEquals('critical', $routingKey);

            $amqpTable = Arr::get($msg->get_properties(), 'application_headers', new AMQPTable);
            $amqpTable->set('x-custom-header', '523292956497346007734586');

            $msg->set('application_headers', $amqpTable);

            return $msg;
        });

        $msg = $lifecycle->triggerBeforePublish(new AMQPMessage('example'), 'logs', 'critical');

        $this->assertTrue($exec, 'Failed asserting that registered callback was executed');

        /** @var AMQPTable $amqpTable */
        $amqpTable = Arr::get($msg->get_properties(), 'application_headers', new AMQPTable);
        $this->assertEquals('523292956497346007734586', Arr::get($amqpTable->getNativeData(), 'x-custom-header'));
    }

    /**
     * @throws UnrecalledAMQPMessageException
     */
    public function test_before_publish_callback_that_doesnt_return_message_throws_exception()
    {
        $this->expectException(UnrecalledAMQPMessageException::class);

        $lifecycle = new LifecycleManager();
        $lifecycle->beforePublish(function ($msg, $exchangeName, $routingKey) {
            //
        });

        $lifecycle->triggerBeforePublish(new AMQPMessage('example'), 'logs', 'critical');
    }

    public function test_published()
    {
        $exec = false;

        $lifecycle = new LifecycleManager();
        $lifecycle->published(function ($msg, $exchangeName, $routingKey) use (&$exec) {
            $exec = true;

            $this->assertEquals('example', $msg->body);
            $this->assertEquals('logs', $exchangeName);
            $this->assertEquals('critical', $routingKey);

            return $msg;
        });

        $lifecycle->triggerPublished(new AMQPMessage('example'), 'logs', 'critical');

        $this->assertTrue($exec, 'Failed asserting that registered callback was executed');
    }

    /**
     * @throws UnrecalledAMQPMessageException
     */
    public function test_before_consume()
    {
        $exec = false;

        $lifecycle = new LifecycleManager();
        $lifecycle->beforeConsume(function (AMQPMessage $msg, $exchangeName, $handlerClass) use (&$exec) {
            $exec = true;

            $this->assertEquals('example', $msg->body);
            $this->assertEquals('logs', $exchangeName);
            $this->assertEquals('ProcessLogsHandler', $handlerClass);

            $amqpTable = Arr::get($msg->get_properties(), 'application_headers', new AMQPTable);
            $amqpTable->set('x-custom-header', '523292956497346007734586');

            $msg->set('application_headers', $amqpTable);

            return $msg;
        });

        $msg = $lifecycle->triggerBeforeConsume(new AMQPMessage('example'), 'logs', 'ProcessLogsHandler');

        $this->assertTrue($exec, 'Failed asserting that registered callback was executed');

        /** @var AMQPTable $amqpTable */
        $amqpTable = Arr::get($msg->get_properties(), 'application_headers', new AMQPTable);
        $this->assertEquals('523292956497346007734586', Arr::get($amqpTable->getNativeData(), 'x-custom-header'));
    }

    /**
     * @throws UnrecalledAMQPMessageException
     */
    public function test_before_consume_callback_that_doesnt_return_message_throws_exception()
    {
        $this->expectException(UnrecalledAMQPMessageException::class);

        $lifecycle = new LifecycleManager();
        $lifecycle->beforeConsume(function ($msg, $exchangeName, $handlerClass) {
            //
        });

        $lifecycle->triggerBeforeConsume(new AMQPMessage('example'), 'logs', 'ProcessLogsHandler');
    }

    public function test_consumed()
    {
        $exec = false;

        $lifecycle = new LifecycleManager();
        $lifecycle->consumed(function ($msg, $exchangeName, $handlerClass, $ack) use (&$exec) {
            $exec = true;

            $this->assertEquals('example', $msg->body);
            $this->assertEquals('logs', $exchangeName);
            $this->assertEquals('ProcessLogsHandler', $handlerClass);

            $this->assertEquals(Ack::MODE_REJECT, $ack->mode);
            $this->assertTrue($ack->requeue);
            $this->assertFalse($ack->multiple);

            return $msg;
        });

        $lifecycle->triggerConsumed(
            new AMQPMessage('example'),
            'logs',
            'ProcessLogsHandler',
            new Ack(Ack::MODE_REJECT, true, false)
        );

        $this->assertTrue($exec, 'Failed asserting that registered callback was executed');
    }
}