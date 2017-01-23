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
// Initialize a Bowler Connection
$connection = new Vinelab\Bowler\Connection();

// Initialize a Producer object with a connection
$bowlerProducer = new Vinelab\Bowler\Producer($connection);

// Setup the producer's exchange name and other optional parameters: exchange type, routing key, passive, durable, auto delete and delivery mode
$bowlerProducer->setup('reporting_exchange', 'direct', 'warning', false, true, false, 2);

// Publish a message
$bowlerProducer->publish($data);
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

        $producer->publish(json_encode($this->data));
    }
}
```

> You need to make sure the exchange setup here matches the consumer's, otherwise a `Vinelab\Bowler\Exceptions\DeclarationMismatchException` is thrown.

> If you mistakenly set an undefined value, setting up the exchange, e.g. $exchangeType='noneExistingType' a `Vinelab\Bowler\Exceptions\InvalidSetupException` is thrown.

### Consumer

Add `'Registrator' => Vinelab\Bowler\Facades\Registrator::class,` to the aliases array in `config/app`.

Configuring the consumer can be done both manually or from the command line:

##### Manually
1. Register your queues and handlers inside the `queues.php` file (think about the queues file as the routes file from Laravel), note that the `queues.php` file should be under `App\Messaging` directory:

    ```php

    Registrator::queue('books', 'App\Messaging\Handlers\BookHandler');

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

    > Similarly to the above, additional functionality is also provided to the consumer's handler like `deleteExchange`, `purgeQueue` and `deleteQueue`. Use these wisely and take advantage of the `unused` and `empty` parameters. Keep in mind that is not recommended that an application exception be handled by manipulating the server's setup.

##### Console
Register queues and handlers with `php artisan bowler:queue analytics_queue analytics_data_exchange`.

The previous command:

1. Adds `Registrator::queue('analytics_queue', 'App\Messaging\Handlers\AnalyticsDataHandler');` to `App\Messaging\queues.php`.

    > If no exchange name is provided the queue name will be used as default.

    The options array, if specified overrides any of the parameters set from the command line.

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

#### 1. Publish the Message
In your Producer:

```php
// Initialize a Bowler object with the rabbitmq server ip and port
$connection = new Bowler\Connection();

// Initialize a Pubisher object with a connection and a routingKey
$bowlerPublisher = new Publisher($connection);

// Set the message routing key
$bowlerPublisher->setRoutingKey('warning');

// Publish the message
$bowlerPublisher->publish($data);
```

> Or inject the Publisher as seen [here](### Producer).

As you might have noted, here we instantiate a `Publisher` not a `Producer` object. Publisher is a Producer specification, it holds the default Pub/Sub **exchange** setup. Mocking the Publisher should be made partial for testing.

#### 2. Consume the Message
In your Consumer:

##### i. Register the queue and generate it's message handler
In your Consumer; from the command line use the `bowler:subscribe` command.

`php artisan bowler:subscribe reporting ReportingMessage --expressive`

Using the `--expressive` or `-E` option will make the queue name reflect that it is used for Pub/Sub. Results in `reporting-pub-sub` as the generated queue name; otherwise the queue name you provided will be used.

Add the `bindingKeys` array parameter to the registered queue in `queues.php` like so:

```php
Registrator::subscribe('reporting-pub-sub', 'App\Messaging\Handlers\ReportingMessageHandler', ['warning']);
```

##### ii. Handle messages
Like we've seen [earlier](##### Manually).

##### iii. Run the queue
From the command line use the `bowler:consume` command.

`php artisan bowler:consume reporting-pub-sub`

> The Pub/Sub implementation is meant to be used as-is. It is possible to Publish a message to all consumers, by setting the routingKey to `null` when publishing the message, and by adding `null` to the consumer's bindingKeys array. If you would like to manually do the configuration, you can surely do so by setting up the Producer and Consumer as explained [earlier](## Usage).

### Testing
Bind your Producer/Publisher to a mock, to restrict it from actually publishing messages to an exchange.

Use `Vinelab\Bowler\Publisher` in `App\Tests\TestCase`;

Add the following to `App\Tests\TestCase::createApplication()`:

```php
$app->bind(Publisher::class, function () {
    return $this->createMock(Publisher::class);
});
````

Since Publisher extends Producer, you should partialy mock Publisher in your tests.

### Dead Lettering
Dead lettering is solely the responsability of the consumer and part of it's queue configuration.
Enabeling dead lettering on the consumer is done through the command line using the same command that run the consumer with the dedicated optional arguments. At least one of the `--deadLetterQueueName` or `--deadLetterExchangeName` options should be specified.
```php
php artisan bowler:consume my_app_queue --deadLetterQueueName=my_app_dlq --deadLetterExchangeName=dlx --deadLetterExchangeType=direct --deadLetterRoutingKey=invalid --messageTTL=10000
```

> If only one of the mentioned optional parameters are set, the second will default to it. Leading to the same `dlx` and `dlq` name.

### Error Handling
Error Handling in Bowler is limited to application exceptions.

`Handler::handleError($e, $broker)` allows you to perfom action on the queue. Whether to acknowledge or reject a message is up to you.

It is not recommended to alter the Rabbitmq setup in reponse to an application exception, e.g. For an `InvalidInputException` to purge the queue! In nay case, if deemed necessary for the use case, it should be used with caution since you will loose all the queued messages.

While server exceptions will be thrown. Server errors not wrapped by Bowler will be thrown as `Vinelab\Bowler\Exceptions\BowlerGeneralException`.

### Error Reporting

Bowler supports application level error reporting.

To do so, the default laravel exception handler normaly located in `app\Exceptions\Handler`, should implement `Vinelab\Bowler\Contracts\BowlerExceptionHandler`. And obviously, implement its methods.

`ExceptionHandler::reportQueue($e, $msg)` allows you to report errors as you wish. While providing the exception and the queue message itsef for maximum flexibility.

### Important Notes
1. It is of most importance that the users of this package, take onto their responsability the mapping between exchanges and queues. And to make sure that exchanges declaration are matching both on the producer and consumer side, otherwise a `Vinelab\Bowler\Exceptions\DeclarationMismatchException` is thrown.

2. The use of nameless exchanges and queues is not supported in this package. Can be reconsidered later.

## TODO
* Write tests.
