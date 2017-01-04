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
            $this->consumer->ackMessage();
        }
    }

    public function setConsumer($consumer)
    {
        $this->consumer = $consumer;
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
`php artisan bowler:consume`, you wil be asked to specify queue name (the queue name is the first parameter passed to `Registrator::queue`)

### Exception Handling
Error Handling in Bowler is split into the application and queue domains.
* `ExceptionHandler::renderQueue($e, $msg)` allows you to render error as you wish. While providing the exception and the que message itsef for maximum flexibility.

* `Handler::handleError($e, $msg)` allows you to perfom action of the messaging queue itself. Whether to acknowledge or reject a message is up to you.

### Exception Reporting

Bowler supports application level error reporting.

To do so the default laravel exception handler normaly located in `app\Exceptions\Handler`, should implement `Vinelab\Bowler\Contracts\BowlerExceptionHandler`.
And obviously, implement its methods.
