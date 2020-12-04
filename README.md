# Bowler
A Laravel package that implements the AMQP protocol using the *[Rabbitmq Server](https://www.rabbitmq.com)* easily and efficiently. Built on top of the *[php-amqplib](https://github.com/php-amqplib/php-amqplib/tree/master/PhpAmqpLib)*, with the aim of providing a simple abstraction layer to work with.

[![Build Status](https://travis-ci.org/Vinelab/bowler.svg?branch=master)](https://travis-ci.org/Vinelab/bowler)

Bowler allows you to:

* Customize message publishing.
* Customize message consumption.
* Customize message dead lettering.
* Handle application errors and deal with the corresponding messages accordingly.
* Provide an expressive consumer queue setup.
* Register a queue and generate it's message handler from the command line.
* Make use of a default Pub/Sub messaging.

In addition to the above Bowler offers limited admin functionalities.

These features will facilitate drastically the way you use Rabbitmq and broaden its functionality. This package do not intend to take over the user's responsability of designing the messaging schema.

Tools like the Rabbitmq *[Management](https://www.rabbitmq.com/management.html)* plugin, will certainly help you monitor the server's activity and visualize the setup.

Table of Contents

[Setup](##Setup)<br>
[Usage](##Usage)<br>
&nbsp;&nbsp;&nbsp;[Producer](###Producer)<br>
&nbsp;&nbsp;&nbsp;[Consumer](###Consumer)<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Manual](####Manual)<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Console](####Console)<br>
&nbsp;&nbsp;&nbsp;[Publisher/Subscriber](###Publisher/Subscriber)<br>
&nbsp;&nbsp;&nbsp;[Dispatcher](###Dispatcher (Work Queue))<br>
&nbsp;&nbsp;&nbsp;[Dead Lettering](###Dead Lettering)<br>
&nbsp;&nbsp;&nbsp;[Error Handling](###Error Handling)<br>
&nbsp;&nbsp;&nbsp;[Error Reporting](###Error Reporting)<br>
&nbsp;&nbsp;&nbsp;[Health Checks](###Health Checks)<br>
&nbsp;&nbsp;&nbsp;[Lifecycle Hooks](###Lifecycle Hooks)<br>
&nbsp;&nbsp;&nbsp;[Testing](###Testing)<br>
[Notes](##Importtant Notes)<br>
[Todo](##TODO)<br>

## Supported Laravel versions
Starting version [v0.4.2](https://github.com/Vinelab/bowler/releases/tag/v0.4.2) this library requires Laravel 5.4 or later versions.

## Setup

Install the package via Composer:

```sh
composer require vinelab/bowler
```

**Laravel 5.4** users will also need to add the service provider to the `providers` array in `config/app.php`:

```php
Vinelab\Bowler\BowlerServiceProvider::class,
```

After installation, you can publish the package configuration using the `vendor:publish` command. This command will publish the `bowler.php` configuration file to your config directory:

```sh
php artisan vendor:publish --provider="Vinelab\Bowler\BowlerServiceProvider"
```

You may configure RabbitMQ credentials in your `.env` file:

```php
RABBITMQ_HOST=localhost
RABBITMQ_PORT=5672
RABBITMQ_USERNAME=guest
RABBITMQ_PASSWORD=guest
```

## Usage

### Producer
In order to be able to send a message, a producer instance needs to be created and an exchange needs to be set up.

```php
// Initialize a Bowler Connection
$connection = new Vinelab\Bowler\Connection();

// Initialize a Producer object with a connection
$bowlerProducer = new Vinelab\Bowler\Producer($connection);

// Setup the producer's exchange name and other optional parameters: exchange type, passive, durable, auto delete and delivery mode
$bowlerProducer->setup('reporting_exchange', 'direct', false, true, false, 2);

// Send a message with an optional routingKey
$bowlerProducer->send($data, 'warning');
```

or Inject the producer and let the IOC resolve the connection:

```php
use Vinelab\Bowler\Producer;

class DoSomethingJob extends Job
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle(Producer $producer)
    {
        $producer->setup('reporting_exchange');

        $producer->send(json_encode($this->data));
    }
}
```

> You need to make sure the exchange setup here matches the consumer's, otherwise a `Vinelab\Bowler\Exceptions\DeclarationMismatchException` is thrown.

> If you mistakenly set an undefined value, setting up the exchange, e.g. `$exchangeType='noneExistingType'` a `Vinelab\Bowler\Exceptions\InvalidSetupException` is thrown.

### Consumer

Add `'Registrator' => Vinelab\Bowler\Facades\Registrator::class,` to the aliases array in `config/app`.

In order to consume a message an exchange and a queue needs to be set up and a message handler needs to be created.

Configuring the consumer can be done both manually or from the command line:

#### Manual
1. Register your queues and handlers inside the `queues.php` file (think about the queues file as the routes file from Laravel), note that the `queues.php` file should be under `App\Messaging` directory:

    ```php

    Registrator::queue('books', 'App\Messaging\Handlers\BookHandler', []);

    Registrator::queue('reporting', 'App\Messaging\Handlers\ErrorReportingHandler', [
                                                            'exchangeName' => 'main_exchange',
                                                            'exchangeType'=> 'direct',
                                                            'bindingKeys' => [
                                                                'warning',
                                                                'notification'
                                                            ],
                                                            'pasive' => false,
                                                            'durable' => true,
                                                            'autoDelete' => false,
                                                            'deadLetterQueueName' => 'dlx_queue',
                                                            'deadLetterExchangeName' => 'dlx',
                                                            'deadLetterExchangeType' => 'direct',
                                                            'deadLetterRoutingKey' => 'warning',
                                                            'messageTTL' => null
                                                        ]);

    ```

    Use the options array to setup your queues and exchanges. All of these are optional, defaults will apply to any parameters that are not specified here. The descriptions and defaults of these parameters are provided later in this document.

2. Create your handlers classes to handle the received messages:

    ```php
    // This is an example handler class

    namespace App\Messaging\Handlers;

    class AuthorHandler {

    	public function handle($msg)
    	{
    		echo "Author: ".$msg->body;
    	}

        public function handleError($e, $broker)
        {
            if($e instanceof InvalidInputException) {
                $broker->rejectMessage();
            } elseif($e instanceof WhatEverException) {
                $broker->ackMessage();
            } elseif($e instanceof WhatElseException) {
                $broker->nackMessage();
            } else {
                $msg = $broker->getMessage();
                if($msg->body) {
                    //
                }
            }
        }
    }
    ```

    > Similarly to the above, additional functionality is also provided to the consumer's handler like `deleteExchange`, `purgeQueue` and `deleteQueue`. Use these wisely and take advantage of the `unused` and `empty` parameters. Keep in mind that it is not recommended that an application exception be handled by manipulating the server's setup.

#### Console
Register queues and handlers with `php artisan bowler:make:queue {queue} {handler}`
e.g. `php artisan bowler:make:queue analytics_queue AnalyticsData`.

The previous command:

1. Adds `Registrator::queue('analytics_queue', 'App\Messaging\Handlers\AnalyticsDataHandler', []);` to `App\Messaging\queues.php`.

    > If no exchange name is provided the queue name will be used as default.

    The options array: if any option is specified it will override the parameter set in the command.
    This help to emphasize the `queues.php` file as a setup reference.

2. Creates the `App\Messaging\Handlers\AnalyticsDataHandler.php` in `App\Messaging\Handlers` directory.

Now, in order to listen to any queue, run the following command from your console: `php artisan bowler:consume {queue}`
e.g. `php artisan bowler:consume analytics_queue`.
You need to specify the queue name and any other optional parameter, if applicable to your case.

`bowler:consume` complete arguments list description:

```php
bowler:consume
queueName : The queue NAME
--N|exchangeName : The exchange NAME. Defaults to queueName.
--T|exchangeType : The exchange TYPE. Supported exchanges: fanout, direct, topic. Defaults to fanout.
--K|bindingKeys : The consumer\'s BINDING KEYS array.
--p|passive : If set, the server will reply with Declare-Ok if the exchange and queue already exists with the same name, and raise an error if not. Defaults to 0.
--d|durable : Mark exchange and queue as DURABLE. Defaults to 1.
--D|autoDelete : Set exchange and queue to AUTO DELETE when all queues and consumers, respectively have finished using it. Defaults to 0.
--deadLetterQueueName : The dead letter queue NAME. Defaults to deadLetterExchangeName.
--deadLetterExchangeName : The dead letter exchange NAME. Defaults to deadLetterQueueName.
--deadLetterExchangeType : The dead letter exchange TYPE. Supported exchanges: fanout, direct, topic. Defaults to fanout.
--deadLetterRoutingKey : The dead letter ROUTING KEY.
--messageTTL : If set, specifies how long, in milliseconds, before a message is declared dead letter.
```

> Consuming a none registered queue will throw `Vinelab\Bowler\Exceptions\UnregisteredQueueException`.

If you wish to handle a message based on the routing key it was published with, you can use a switch case in the handler's `handle` method, like so:

```php
public function handle($msg)
{
    switch ($msg->delivery_info['routing_key']) {
        case 'key 1': //do something
            break;
        case 'key 2': //do something else
            break;
    }
}
```

### Publisher/Subscriber
Bowler provide a default Pub/Sub implementation, where the user doesn't need to care about the setup.

In short, publish with a `routingKey` and consume with matching `bindingKey(s)`.

#### 1. Publish

_In your Producer_

```php
// Initialize a Bowler object with the rabbitmq server ip and port
$connection = new Bowler\Connection();

// Initialize a Pubisher object with a connection and a routingKey
$bowlerPublisher = new Publisher($connection);

// Publish the message and set its required routingKey
$bowlerPublisher->publish('warning', $data);
```

> Or inject the Publisher similarly to what we've seen [here](###Producer).

As you might have noted, here we instantiate a `Publisher` not a `Producer` object. Publisher is a Producer specification, it holds the default Pub/Sub **exchange** setup.

##### Signature
```php
publish($routingKey, $data = null);
```

#### 2. Subscribe

_In your Consumer_

##### i. Register the queue and generate its message handler
From the command line use the `bowler:make:subscriber` command.

`php artisan bowler:make:subscriber reporting ReportingMessage --expressive`

Using the `--expressive` or `-E` option will make the queue name reflect that it is used for Pub/Sub. Results in `reporting-pub-sub` as the generated queue name; otherwise the queue name you provided will be used.

Add the `bindingKeys` array parameter to the registered queue in `queues.php` like so:

```php
Registrator::subscriber('reporting-pub-sub', 'App\Messaging\Handlers\ReportingMessageHandler', ['warning']);
```

> Notice that it is not required to specify the exchange since it uses the default `pub-sub` exchange.

##### ii. Handle messages
Like we've seen [earlier](####Manual).

##### iii. Run the consumer
From the command line use the `bowler:consume` command.

`php artisan bowler:consume reporting-pub-sub`

The Pub/Sub implementation is meant to be used as-is. It is possible to consume all published messages to a given exchange by setting the consumer's binding keys array to `['*']`.

> If no binding keys are provided a `Vinelab\Bowler\Exception\InvalidSubscriberBindingException` is thrown.

If you would like to configure manually, you can surely do so by setting up the Producer and Consumer as explained [earlier](##Usage).

##### Signature
```php
Registrator::subscriber($queue, $className, array $bindingKeys, $exchangeName = 'pub-sub', $exchangeType = 'topic');
```

### Dispatcher (Work Queue)
Similar to Pub/Sub, except you may define the exchange and messages will be distributed according to the least busy
consumer (see [Work Queue - Fair Dispatch](https://www.rabbitmq.com/tutorials/tutorial-two-php.html)).

#### 1. Dispatch
Dispatch messages to a specific exchange with a `routingKey` and consume with matching `bindingKey(s)`.

```php
// Initialize a Bowler object with the rabbitmq server ip and port
$connection = new Bowler\Connection();

// Initialize a Dispatcher object with a connection
$dispatcher = new Dispatcher($connection);

// Publish the message and set its required exchange name and routingKey
$dispatcher->dispatch('my-custom-exchange', 'warning', $data);
```
##### Signature
```php
dispatch($exchangeName, $routingKey, $data = null, $exchangeType = 'topic')
```

#### 2. Consume
Registering a queue consumer is the same as Pub/Sub, except the exchange name in the registration needs to match.

```php
// catch all the cows in the "farm" exchange
Registrator::subscriber('monitoring', 'App\Messaging\Handlers\MonitoringMessageHandler', [
    '*.cow.*',
], 'farm');
```

The above will catch all the messages in the `farm` exchange that match the routing key `*.cow.*`

### Dead Lettering
Dead lettering is solely the responsability of the consumer and part of it's queue configuration.
Enabeling dead lettering on the consumer is done through the command line using the same command that run the consumer with the dedicated optional arguments or by setting the corresponding optional parameters in the aforementioned, options array.
At least one of the `--deadLetterQueueName` or `--deadLetterExchangeName` options should be specified.

```php
php artisan bowler:consume my_app_queue --deadLetterQueueName=my_app_dlq --deadLetterExchangeName=dlx --deadLetterExchangeType=direct --deadLetterRoutingKey=invalid --messageTTL=10000
```

> If only one of the mentioned optional parameters are set, the second will default to it. Leading to the same `dlx` and `dlq` name.

If you would like to avoid using Dead Lettering, you could leverage a striped down behaviour, by requeueing dead messages using `$broker->rejectMessage(true)` in the queue's `MessageHandler::handleError()`.

### Error Handling
Error Handling in Bowler is limited to application exceptions.

`Handler::handleError($e, $broker)` allows you to perfom action on the queue. Whether to acknowledge, nacknowledge or reject a message is up to you.

It is not recommended to alter the Rabbitmq setup in reponse to an application exception, e.g. for an `InvalidInputException` to purge the queue!
In any case, if deemed necessary for the use case, it should be used with caution since you will loose all the queued messages or even worst, your exchange.

While server exceptions will be thrown. Server errors not wrapped by Bowler will be thrown as `Vinelab\Bowler\Exceptions\BowlerGeneralException`.

### Error Reporting

Bowler supports application level error reporting.

To do so, the default laravel exception handler normaly located in `app\Exceptions\Handler`, should implement `Vinelab\Bowler\Contracts\BowlerExceptionHandler`.

`ExceptionHandler::reportQueue(Exception $e, AMQPMessage $msg)` allows you to report errors as you wish. While providing the exception and the queue message itsef for maximum flexibility.

### Health Checks

**IMPORTANT: Management plugin is required to be installed in order to perform health checks.**

Based on [this Reliability Guide](https://www.rabbitmq.com/reliability.html), Bowler figured that it would be beneficial to provide
a tool to check the health of connected consumers and is provided through the `bowler:healthcheck:consumer` command with the following signature:

```
bowler:healthcheck:consumer {queueName : The queue name}
```

Example: `php artisan bowler:healthcheck:consumer the-queue`

Will return exit code `0` for success and `1` for failure along with a message why.

### Lifecycle Hooks

Bowler exposes the following lifecycle hooks:

```php
use Vinelab\Bowler\Facades\Message;

Message::beforePublish(function (AMQPMessage $msg, string $exchangeName, $routingKey = null) {
  return $msg;
});

Message::published(function (AMQPMessage $msg, string $exchangeName, $routingKey = null) {
  //
});

Message::beforeConsume(function (AMQPMessage $msg, string $queueName, string $handlerClass) {
  return $msg;
});

Message::consumed(function (AMQPMessage $msg, string $queueName, string $handlerClass, Ack $ack) {
  //
})
```

By default, Bowler logs and suppress errors that occur inside the callback. You can configure this behavior via `bowler.lifecycle_hooks.fail_on_error` configuration option.

### Testing
If you would like to silence the Producer/Publisher to restrict it from actually sending/publishing messages to an exchange, bind it to a mock, locally in your test or globally.

Globally:

Use `Vinelab\Bowler\Producer` in `App\Tests\TestCase`;

Add the following to `App\Tests\TestCase::createApplication()`:

```php
$app->bind(Producer::class, function () {
    return $this->createMock(Producer::class);
});
````

## Important Notes
1. It is of most importance that the users of this package, take onto their responsability the mapping between exchanges and queues. And to make sure that exchanges declaration are matching both on the producer and consumer side, otherwise a `Vinelab\Bowler\Exceptions\DeclarationMismatchException` is thrown.

2. The use of nameless exchanges and queues is not supported in this package. Can be reconsidered later.

## TODO
* Write tests.
* Become framework agnostic.
