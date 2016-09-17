# Bowler
A package that makes AMQP protocol implementation using rabbitmq server easy and straightforward.

## Installation

### Composer
```json
{
    "require": {
        "vinelab/bowler"
    }
}
```

## Usage
### Producer
```
// initialize a Bowler object with the rabbitmq server ip and port
$connection = new Bowler\Connection('localhost', 5672);
// initialize a Producer object with a connection, exchange name and type
$bowlerProducer = new Producer($bowler, 'crud', 'fanout');
return $bowlerProducer->publishToExchange($data, 'route');
```

### Consumer

```
// create a class to handle the messages and route them accordingly
<?php

namespace App\Test;

class Handler {

	public function handle($msg)
	{
		echo "Handler: ".$msg->body;
	}
}
```

```
$bowler = new Bowler('localhost', 5672);
$bowlerConsumer = new Consumer($bowler, 'crud', 'fanout');

$handler = new App\Messaging\Handler();
$bowlerConsumer->listenToQueue($handler, 'books');
```
