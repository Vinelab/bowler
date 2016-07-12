# Bowler
A library to make the communication between your php application and rabbitmq server with AMQP protocol easier.

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
$bowler = new Bowler('localhost', 5672);
// initialize  a Producer object with a bowler object, exchange name and type
$bowlerProducer = new Producer($bowler, 'crud', 'fanout');
return $bowlerProducer->publishToExchange($this->book, 'books');
```

### Consumer

```
// create a class to handle the messages and route them accordingly
<?php

namespace App\Test;

class Handler {

	public function __construct($msg = '')
	{
		if ($msg) {
			$json = json_decode(json_encode($msg), true);
			if ($json['routing_key'] == 'books') {
				echo 'Books: ' . $json['body'];
			} else {
				echo 'Authors: ' . $WorkingArray['body'];
			}
		}
	}
}
```

```
$bowler = new Bowler('localhost', 5672);
$bowlerConsumer = new Consumer($bowler, 'crud', 'fanout');

// 
$handler = new App\Messaging\Handler();
// $bowlerConsumer->listenToQueue($handler);
$bowlerConsumer->listenToQueue($handler, 'books');
```
