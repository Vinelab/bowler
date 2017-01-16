# Bowler
A package that makes AMQP protocol implementation using rabbitmq server easy and straightforward.

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
// initialize a Bowler object with the rabbitmq server ip and port
$connection = new Bowler\Connection();
// initialize a Producer object with a connection, exchange name and type
$bowlerProducer = new Producer($connection, 'reporting_exchange', 'direct', 'warning', false, true, false, 2);
// publish a message
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
//this is an example handler class

namespace App\Messaging\Handlers;

use Vinelab\Bowler\Exceptions\DeclarationMismatchException;

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
        } elseif($e instanceof DeclarationMismatchException) {
            // This can help you delete the existing queue, which allows the consumer to recreate the queue by overriding the current setup on the next consumed message.
            // Its a way to programatically handle such situations, but should be used with caution since you will loose all the queued messages.
            // Moreover, the producer will need to be instantiated with the new setup. Which put this use case at question!
            $this->consumer->deleteQueue(true, true);
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
        case 'key 2': //do something else
    }
}
```

- Add all your handlers inside the queues.php file (think about the queues file as the routes file from Laravel), note that the `queues.php` file should be under App\Messaging directory:

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
                                                        'deadLetterExchangeName' => 'warning',
                                                        'deadLetterRoutingKey' => 'warning',
                                                        'messageTTL' => null
                                                    ]);

```

Use the options array to setup your queues and exchanges. All of these are optional, defaults will apply to any parameters that are not specified here. The descriptions and defaults of these parameters are provided later in this document.

##### Console
- Register a handler for a specific queue with `php artisan bowler:handler analytics_queue analytics_data_exchange`.

The previous command:

1. Adds `Registrator::queue('analytics_queue', 'App\Messaging\Handlers\AnalyticsDataHandler');` to `App\Messaging\queues.php`.

> If no exchange name is provided the queue name will be used for both.

2. Create the `App\Messaging\Handlers\AnalyticsDataHandler.php` in `App\Messaging\Handler` directory.

- Now in order to listen to any queue, run the following command from your console:
`php artisan bowler:consume`, you will be asked to specify queue name (the queue name is the first parameter passed to `Registrator::queue`).

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
--messageTtl : If set, specifies how long, in milliseconds, before a message is declared dead letter
```

### Dead Lettering
Since dead lettering is solely the responsability of the consumer and part of it's queue configuration, the natural place to define one.
Enabeling dead lettering on the consumer is done through the command line using the same command that run the consumer with the dedicated optional arguments, at least one of `--deadLetterQueueName` or `--deadLetterExchangeName` should be specified.
```php
php artisan bowler:consume my_app_queue --deadLetterQueueName=my_app_dlx --deadLetterExchangeName=dlx --deadLetterExchangeType=direct --deadLetterRoutingKey=invalid --messageTTL=10000
```

> If only one of the mentioned optional arguments are set, the second will default to the exact value as to the one you've just set. Leading to the same dlx and dlq name.

### Exception Handling
Error Handling in Bowler is split into the application and queue domains.
* `ExceptionHandler::renderQueue($e, $msg)` allows you to render error as you wish. While providing the exception and the que message itsef for maximum flexibility.

* `Handler::handleError($e, $msg)` allows you to perfom action of the messaging queue itself. Whether to acknowledge or reject a message is up to you.

### Exception Reporting

Bowler supports application level error reporting.

To do so the default laravel exception handler normaly located in `app\Exceptions\Handler`, should implement `Vinelab\Bowler\Contracts\BowlerExceptionHandler`.
And obviously, implement its methods.

### Important Notes
1- It is of most importance that the users of this package, take onto their responsability the mapping between exchanges and queues. And to make sure that exchanges declaration are matching both on the producer and consumer side, otherwise a `Vinelab\Bowler\Exceptions\DeclarationMismatchException` is thrown.

2- The use of nameless exchanges and queues is not supported in this package. Can be reconsidered later.

## TODO
* Expressive queue declaration.
* Provide default pub/sub and dlx implementations.
* Provide a way to programatically handle configuration exceptions.
* Write tests.
