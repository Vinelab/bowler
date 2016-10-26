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

- Create your handlers classes to handle the messages received: 

```php
//this is an example handler class

namespace App\Messaging;

class AuthorHandler {

	public function handle($msg)
	{
		echo "Author: ".$msg->body;
	}
}
```

- Add all your handlers inside the queues.php file (think about the queues file as the routes file from Laravel), note that the `queues.php` file should be under App\Messaging folder:

```php

Registrator::queue('books', 'App\Messaging\BookHandler');

Registrator::queue('crud', 'App\Messaging\AuthorHandler');

```

- Now in order to listen to any queue, run the following command from your console:
`php artisan bowler:consume`, you wil be asked to specify queue name (the queue name is the first parameter passed to `Registrator::queue`)
