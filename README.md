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

## Installation

### Composer
```php
{
    "require": {
        "vinelab/bowler"
    }
}
```

## Usage

### Configuration

In order to configure rabbitmq host, port, username and password, add the following inside the connections array in config/queue.php file:

```php
'rabbitmq' => [
    'host' => 'host',
    'port' => port,
    'username' => 'username',
    'password' => 'password',
],
```

And register the service provider by adding `Vinelab\Bowler\BowlerServiceProvider::class` to the providers array in `config/app`.

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

> If you mistakenly set an undefined value, setting up the exchange, e.g. $exchangeType='noneExistingType' a `Vinelab\Bowler\Exceptions\InvalidSetupException` is thrown.

### Consumer

Add `'Registrator' => Vinelab\Bowler\Facades\Registrator::class,` to the aliases array in `config/app`.

In order to consume a message an exchange and a queue needs to be set up and a message handler needs to be created.

Configuring the consumer can be done both manually or from the command line:

##### Manually
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
    //This is an example handler class

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
                $msg = $borker->getMessage();
                if($msg->body) {
                    //
                }
            }
        }
    }
    ```

    > Similarly to the above, additional functionality is also provided to the consumer's handler like `deleteExchange`, `purgeQueue` and `deleteQueue`. Use these wisely and take advantage of the `unused` and `empty` parameters. Keep in mind that it is not recommended that an application exception be handled by manipulating the server's setup.

##### Console
Register queues and handlers with `php artisan bowler:make:queue analytics_queue AnalyticsData`.

The previous command:

1. Adds `Registrator::queue('analytics_queue', 'App\Messaging\Handlers\AnalyticsDataHandler', []);` to `App\Messaging\queues.php`.

    > If no exchange name is provided the queue name will be used as default.

    The options array, if specified overrides any of the parameters set from the command line. Beside, it serve as a setup reference.

2. Creates the `App\Messaging\Handlers\AnalyticsDataHandler.php` in `App\Messaging\Handlers` directory.

Now in order to listen to any queue, run the following command from your console:
`php artisan bowler:consume analytics_queue`. You need to specify the queue name and any other optional parameter, if applicable to your case.

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

### Publish/Subscribe
Bowler provide a default Pub/Sub implementation, where the user doesn't need to care about the setup.

In short, publish with a `routingKey` and consume with matching `bindingKeys`.

#### 1. Publish the Message
In your Producer:

```php
// Initialize a Bowler object with the rabbitmq server ip and port
$connection = new Bowler\Connection();

// Initialize a Pubisher object with a connection and a routingKey
$bowlerPublisher = new Publisher($connection);

// Publish the message and set its required routingKey
$bowlerPublisher->publish('warning', $data);
```

> Or inject the Publisher similarly to what we've seen [here](### Producer).

As you might have noted, here we instantiate a `Publisher` not a `Producer` object. Publisher is a Producer specification, it holds the default Pub/Sub **exchange** setup.

#### 2. Consume the Message
In your Consumer:

##### i. Register the queue and generate its message handler
In your Consumer; from the command line use the `bowler:make:subscriber` command.

`php artisan bowler:make:subscriber reporting ReportingMessage --expressive`

Using the `--expressive` or `-E` option will make the queue name reflect that it is used for Pub/Sub. Results in `reporting-pub-sub` as the generated queue name; otherwise the queue name you provided will be used.

Add the `bindingKeys` array parameter to the registered queue in `queues.php` like so:

```php
Registrator::subscriber('reporting-pub-sub', 'App\Messaging\Handlers\ReportingMessageHandler', ['warning']);
```

##### ii. Handle messages
Like we've seen [earlier](##### Manually).

##### iii. Run the queue
From the command line use the `bowler:consume` command.

`php artisan bowler:consume reporting-pub-sub`

The Pub/Sub implementation is meant to be used as-is. It is possible to consume all published messages setting the consumer's bindingKeys array to `['*']`.

> If no bindingKeys are provided a `Vinelab\Bowler\Exception\InvalidSubscriberBindingException` is thrown.

If you would like to manually do the configuration, you can surely do so by setting up the Producer and Consumer as explained [earlier](## Usage).

### Testing
If you would like to silence the Producer/Publisher to restrict it from actually sending/publishing messages to an exchange, bind it to a mock, locally in your test or globally.

Globally:
Use `Vinelab\Bowler\Publisher` in `App\Tests\TestCase`;

Add the following to `App\Tests\TestCase::createApplication()`:

```php
$app->bind(Publisher::class, function () {
    return $this->createMock(Publisher::class);
});
````

### Dead Lettering
Dead lettering is solely the responsability of the consumer and part of it's queue configuration.
Enabeling dead lettering on the consumer is done through the command line using the same command that run the consumer with the dedicated optional arguments or by setting the corresponding optional parameters in the before mentioned, options array.
At least one of the `--deadLetterQueueName` or `--deadLetterExchangeName` options should be specified.

```php
php artisan bowler:consume my_app_queue --deadLetterQueueName=my_app_dlq --deadLetterExchangeName=dlx --deadLetterExchangeType=direct --deadLetterRoutingKey=invalid --messageTTL=10000
```

> If only one of the mentioned optional parameters are set, the second will default to it. Leading to the same `dlx` and `dlq` name.

If you would like to avoid using Dead Lettering, you could leverage a striped down behaviour, by requeueing dead messages using `$broker->rejectMessage(true)` in the queue's `MessageHandler::handleError()`.

### Error Handling
Error Handling in Bowler is limited to application exceptions.

`Handler::handleError($e, $broker)` allows you to perfom action on the queue. Whether to acknowledge, nacknowledge or reject a message is up to you.

It is not recommended to alter the Rabbitmq setup in reponse to an application exception, e.g. For an `InvalidInputException` to purge the queue!
In nay case, if deemed necessary for the use case, it should be used with caution since you will loose all the queued messages or even worst, your exchange.

While server exceptions will be thrown. Server errors not wrapped by Bowler will be thrown as `Vinelab\Bowler\Exceptions\BowlerGeneralException`.

### Error Reporting

Bowler supports application level error reporting.

To do so, the default laravel exception handler normaly located in `app\Exceptions\Handler`, should implement `Vinelab\Bowler\Contracts\BowlerExceptionHandler`. And obviously, implement its method.

`ExceptionHandler::reportQueue(Exception $e, AMQPMessage $msg)` allows you to report errors as you wish. While providing the exception and the queue message itsef for maximum flexibility.

### Health Checks

Based on [this Reliability Guide](https://www.rabbitmq.com/reliability.html), Bowler figured that it would be beneficial to provide
a tool to check the health of connected consumers and is provided through the `bowler:healthcheck:consumer` command with the following signature:

```
bowler:healthcheck:consumer
    {queueName : The queue name}
    {--c|consumers=1 : The expected number of consumers to be connected to the queue specified by queueName}
```

Example: `php artisan bowler:healthcheck:consumer the-queue`

Will return exit code `0` for success and `1` for failure along with a message why.

### Important Notes
1. It is of most importance that the users of this package, take onto their responsability the mapping between exchanges and queues. And to make sure that exchanges declaration are matching both on the producer and consumer side, otherwise a `Vinelab\Bowler\Exceptions\DeclarationMismatchException` is thrown.

2. The use of nameless exchanges and queues is not supported in this package. Can be reconsidered later.

## TODO
* Write tests.
* Become framework agnostic.
