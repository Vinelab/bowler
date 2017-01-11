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

### Producer

```php
// initialize a Bowler object with the rabbitmq server ip and port
$connection = new Bowler\Connection();
// initialize a Producer object with a connection, exchange name and type
$bowlerProducer = new Producer($connection, 'crud', 'fanout');
return $bowlerProducer->publish($data);
```

### Consumer

- Modify config/app.php:
	- add `Vinelab\Bowler\BowlerServiceProvider::class,` to the providers array.
	- add `'Registrator' => Vinelab\Bowler\Facades\Registrator::class,` to the aliases array.

##### Manually
- Create your handlers classes to handle the messages received:

```php
//this is an example handler class

namespace App\Messaging;

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

- Add all your handlers inside the queues.php file (think about the queues file as the routes file from Laravel), note that the `queues.php` file should be under App\Messaging folder:

```php

Registrator::queue('books', 'App\Messaging\BookHandler');

Registrator::queue('crud', 'App\Messaging\AuthorHandler');

```

##### Console
- Register a handler for a specific queue with `php artisan bowler:handler analytics_queue AnalyticsData`.

The previous command:

1. Adds `Registrator::queue('analytics_queue', 'App\Messaging\Handlers\AnalyticsDataHandler');` to `App\Messaging\queues.php`.

2. Create the `App\Messaging\Handlers\AnalyticsDataHandler.php` in `App\Messaging\Handler` directory.

- Now in order to listen to any queue, run the following command from your console:
`php artisan bowler:consume`, you will be asked to specify queue name (the queue name is the first parameter passed to `Registrator::queue`)

`bowler:consume` complete arguments list description:

```php
bowler:consume
queueName : The queue NAME
--N|exchangeName= : The exchange NAME. Defaults to queueName
--T|exchangeType=fanout : The exchange TYPE. Supported exchanges: fanout, direct, topic. Defaults to fanout
--K|bindingKeys=* : The consumer\'s BINDING KEYS (array)
--p|passive=0 : If set, the server will reply with Declare-Ok if the exchange and queue already exists with the same name, and raise an error if not. Defaults to 0
--d|durable=1 : Mark exchange and queue as DURABLE. Defaults to 1
--D|autoDelete=0 : Set exchange and queue to AUTO DELETE when all queues and consumers, respectively have finished using it. Defaults to 0
--M|deliveryMode=2 : The message DELIVERY MODE. Non-persistent 1 or persistent 2. Defaults to 2
--deadLetterQueueName= : The dead letter queue NAME. Defaults to deadLetterExchangeName
--deadLetterExchangeName= : The dead letter exchange NAME. Defaults to deadLetterQueueName
--deadLetterExchangeType=fanout : The dead letter exchange TYPE. Supported exchanges: fanout, direct, topic. Defaults to fanout
--deadLetterRoutingKey= : The dead letter ROUTING KEY
--messageTtl= : If set, specifies how long, in milliseconds, before a message is declared dead letter
```

### Dead Lettering
#### Producer
Once a Producer is instantiated, if you wish to enable dead lettering you should:
```php
$producer = new Produer(...);

$producer->configureDeadLettering($deadLetterQueueName, $deadLEtterExchangeName, $deadLetterExchangeType, $deadLetterRoutingKey, $messageTtl);

$producer->publish($body);
```

#### Consumer
Enabeling dead lettering on the consumer is done through the command line using the same command that run the consumer with the dedicated optional arguments, at least one of `--deadLetterQueueName` or `--deadLetterExchangeName` should be specified.
```php
php artisan bowler:consume my_app_queue --deadLetterQueueName=my_app_dlx --deadLetterExchangeName=dlx --deadLetterExchangeType=direct --deadLetterRoutingKey=invalid --messageTtl=10000
```

### Exception Handling
Error Handling in Bowler is split into the application and queue domains.
* `ExceptionHandler::renderQueue($e, $msg)` allows you to render error as you wish. While providing the exception and the que message itsef for maximum flexibility.

* `Handler::handleError($e, $msg)` allows you to perfom action of the messaging queue itself. Whether to acknowledge or reject a message is up to you.

### Exception Reporting

Bowler supports application level error reporting.

To do so the default laravel exception handler normaly located in `app\Exceptions\Handler`, should implement `Vinelab\Bowler\Contracts\BowlerExceptionHandler`.
And obviously, implement its methods.
