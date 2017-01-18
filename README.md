# Bowler
A Laravel package that implements the AMQP protocol using the *[Rabbitmq Server](https://www.rabbitmq.com)* easily and efficiently. Built on top of the *[php-amqplib](https://github.com/php-amqplib/php-amqplib/tree/master/PhpAmqpLib)*, with the aim of providing a simple abstraction layer to work with.

Bowler allows you to:

* Customize message publishing.
* Customize message consumption.
* Customize message dead lettering.
* Handle application errors and deal with the corresponding messages accordingly.
* Provide an expressive consumer queue setup.
* Register a queue and generate it's message handler from the command line.
* Limited admin functionalities.

These features will facilitate drastically the way you use Rabbitmq and broaden its functionality. This package do not intend to take over the user's responsability of designing the messaging queues schema.

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

```php
// Initialize a Bowler object with the rabbitmq server ip and port
$connection = new Bowler\Connection();

// Initialize a Producer object with a connection, exchange name, exchange type, routing key, passive, durable, auto delete and delivery mode
$bowlerProducer = new Producer($connection, 'reporting_exchange', 'direct', 'warning', false, true, false, 2);

// Publish a message
$bowlerProducer->publish($data);
```

> You need to make sure the exchange setup here matches the consumer's, otherwise a `Vinelab\Bowler\Exceptions\DeclarationMismatchException` is thrown.

### Consumer

Add `'Registrator' => Vinelab\Bowler\Facades\Registrator::class,` to the aliases array in `config/app`.

Create a handler where you can handle the received messages and bind the handler to its corresponding queue.

You can do so either:

##### Manually
- Create your handlers classes to handle the messages received:

```php
//This is an example handler class

namespace App\Messaging\Handlers;

class AuthorHandler {

    private $consumer;

	public function handle($msg)
	{
		echo "Author: ".$msg->body;
	}

    public function handleError($e, $msg)
    {
        if($e instanceof InvalidInputException) {
            $this->consumer->rejectMessage($msg);
        } elseif($e instanceof WhatEverException) {
            $this->consumer->ackMessage($msg);
        } elseif($e instanceof WhatElseException) {
            $this->consumer->nackMessage($msg);
        }
    }

    public function setConsumer($consumer)
    {
        $this->consumer = $consumer;
    }
}
```

> Similarly to the above, additional functionality is also provided to the consumer's handler like `deleteExchange`, `purgeQueue` and `deleteQueue`. Use these wisely and take advantage of the `unused` and `empty` parameters.

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

- Register your queues and handlers inside the `queues.php` file (think about the queues file as the routes file from Laravel), note that the `queues.php` file should be under App\Messaging directory:

```php

Registrator::queue('books', 'App\Messaging\Handlers\BookHandler');

Registrator::queue('reporting', 'App\Messaging\Handlers\AuthorHandler', [
                                                        'exchangeName' => 'main_exchange',
                                                        'exchangeType'=> 'direct',
                                                        'bindingKeys' => [
                                                            'warning',
                                                            'notification'
                                                        ],
                                                        'pasive' => false,
                                                        'durable' => true,
                                                        'autoDelete' => false,
                                                        'deliveryMode' => 2,
                                                        'deadLetterQueueName' => 'dlx_queue',
                                                        'deadLetterExchangeName' => 'dlx',
                                                        'deadLetterExchangeType' => 'direct',
                                                        'deadLetterRoutingKey' => 'warning',
                                                        'messageTTL' => null
                                                    ]);

```

Use the options array to setup your queues and exchanges. All of these are optional, defaults will apply to any parameters that are not specified here. The descriptions and defaults of these parameters are provided later in this document.

These parameters overrides any that is set from the command line.

##### Console
- Register queues and handlers with `php artisan bowler:queue analytics_queue analytics_data_exchange`.

The previous command:

1. Adds `Registrator::queue('analytics_queue', 'App\Messaging\Handlers\AnalyticsDataHandler');` to `App\Messaging\queues.php`.

> If no exchange name is provided the queue name will be used for both.

2. Create the `App\Messaging\Handlers\AnalyticsDataHandler.php` in `App\Messaging\Handler` directory.

- Now in order to listen to any queue, run the following command from your console:
`php artisan bowler:consume`, you need to specify the queue name and any other optional parameter, if applicable to your case.

`bowler:consume` complete arguments list description:

```php
bowler:consume
queueName : The queue NAME
--N|exchangeName : The exchange NAME. Defaults to queueName
--T|exchangeType : The exchange TYPE. Supported exchanges: fanout, direct, topic. Defaults to fanout
--K|bindingKeys : The consumer\'s BINDING KEYS array
--p|passive : If set, the server will reply with Declare-Ok if the exchange and queue already exists with the same name, and raise an error if not. Defaults to 0
--d|durable : Mark exchange and queue as DURABLE. Defaults to 1
--D|autoDelete : Set exchange and queue to AUTO DELETE when all queues and consumers, respectively have finished using it. Defaults to 0
--M|deliveryMode : The message DELIVERY MODE. Non-persistent 1 or persistent 2. Defaults to 2
--deadLetterQueueName : The dead letter queue NAME. Defaults to deadLetterExchangeName
--deadLetterExchangeName : The dead letter exchange NAME. Defaults to deadLetterQueueName
--deadLetterExchangeType : The dead letter exchange TYPE. Supported exchanges: fanout, direct, topic. Defaults to fanout
--deadLetterRoutingKey : The dead letter ROUTING KEY
--messageTTL : If set, specifies how long, in milliseconds, before a message is declared dead letter
```

### Publish/Subscribe
Bowler provide a default Pub/Sub implementation, where the user doesn't need to care about the setup.

#### 1. Publish the Message

```php
// Initialize a Bowler object with the rabbitmq server ip and port
$connection = new Bowler\Connection();

// Initialize a Pubisher object with a connection and a routingKey
$bowlerPublisher = new Publisher($connection, 'warning');

// Publish the message
$bowlerPublisher->publish($data);
```

As you might have noted, here we instantiate a `Publisher` not a `Producer` object. Publishers holds the default Pub/Sub **exchange** setup.

#### 2. Register the queue and generate it's message handler
From the command line use the `bowler:subscribe` command.

`php artisan bowler:subscribe reporting ReportingMessage --expressive`

Using the `--expressive` or `-E` option will make the queue name reflect that it is used for Pub/Sub. Results in `reporting-pub-sub` as the generated queue name; otherwise the queue name you provided will be used.

Add the `bindingKeys` array parameter to the registered queue in `queues.php` like so:

```php
Registrator::subscribe('reportin-pub-sub', 'App\Messaging\Handlers\ReportingMessageHandler', ['warning']);
```

#### 3. Handle messages
Like we've seen [earlier](##### Manually).

#### 4. Run the queue
From the command line use the `bowler:consume` command.

`php artisan bowler:consume reporting-pub-sub`

> The Pub/Sub implementation is meant to be used as-is. If you would like to manually do the configuration, you can surely do so by setting up the Producer and Consumer as explained [earlier](## Usage).

### Dead Lettering
Dead lettering is solely the responsability of the consumer and part of it's queue configuration.
Enabeling dead lettering on the consumer is done through the command line using the same command that run the consumer with the dedicated optional arguments. At least one of the `--deadLetterQueueName` or `--deadLetterExchangeName` options should be specified.
```php
php artisan bowler:consume my_app_queue --deadLetterQueueName=my_app_dlq --deadLetterExchangeName=dlx --deadLetterExchangeType=direct --deadLetterRoutingKey=invalid --messageTTL=10000
```

> If only one of the mentioned optional parameters are set, the second will default to it. Leading to the same `dlx` and `dlq` name.

### Exception Handling
Error Handling in Bowler is split into application and messaging domains.
* `ExceptionHandler::renderQueue($e, $msg)` allows you to render errors as you wish. While providing the exception and the queue message itsef for maximum flexibility.

* `Handler::handleError($e, $msg)` allows you to perfom action on the queue. Whether to acknowledge or reject a message is up to you.

It is not recommended to alter the Rabbitmq setup in reponse to an application error,  e.g. For an `InvalidInputException` to delete the queue!

Altering the Rabbitmq setup is not justified unless triggered by a Bowler configuration error e.g. `DeclarationMismatchException`.

### Exception Reporting

Bowler supports application level error reporting.

To do so the default laravel exception handler normaly located in `app\Exceptions\Handler`, should implement `Vinelab\Bowler\Contracts\BowlerExceptionHandler`.
And obviously, implement its methods.

`ExceptionHandler::reportQueue($e, $msg)`

### Important Notes
1- It is of most importance that the users of this package, take onto their responsability the mapping between exchanges and queues. And to make sure that exchanges declaration are matching both on the producer and consumer side, otherwise a `Vinelab\Bowler\Exceptions\DeclarationMismatchException` is thrown.

2- The use of nameless exchanges and queues is not supported in this package. Can be reconsidered later.

## TODO
* Improve Bowler Exception handling.
* Provide a way to programatically handle configuration exceptions.
* Write tests.
