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
$bowler = new Bowler('localhost', 5672);
$bowlerProducer = new Producer($bowler, 'crud', 'fanout');
return $bowlerProducer->publishToExchange($this->book, 'books');
```
