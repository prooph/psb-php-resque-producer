<?php
/*
 * This file is part of the prooph/php-service-bus.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 16.03.14 - 23:46
 */
chdir(__DIR__);

require_once '../../vendor/autoload.php';

include 'classes.php';

//Attach php-resque worker to a queue via environment variable
putenv('QUEUE=resque-sample');

//Set up command bus to dispatch incoming message to it's handler
$commandBus = new \Prooph\ServiceBus\CommandBus();

//In the example we will only receive one type of command so we only define one route
$commandBus->utilize(new \Prooph\ServiceBus\Router\CommandRouter([
    'Prooph\ServiceBus\Example\Resque\WriteLine' => new \Prooph\ServiceBus\Example\Resque\FileWriter(__DIR__ . '/dump.txt')
]));

//The php-resque message consumer will receive a @see \Prooph\ServiceBus\Message\StandardMessage
//Before the FileWriter can handle the message it needs to be translated back to a command
$commandBus->utilize(new \Prooph\ServiceBus\Message\FromRemoteMessageTranslator());

//The FileWriter provides a handleWriteLine method so we can use the HandleCommandStrategy to invoke it
$commandBus->utilize(new \Prooph\ServiceBus\InvokeStrategy\HandleCommandStrategy());

//The CommandBus needs to be globally available so that the message consumer can forward the incoming messages to it
\Prooph\ServiceBus\StaticBusRegistry::setCommandBus($commandBus);

//The StaticBusRegistry requires both bus types to be set. The EventBus won't be used in our example so we just
//register a default EventBus
\Prooph\ServiceBus\StaticBusRegistry::setEventBus(new \Prooph\ServiceBus\EventBus());

include '../../vendor/chrisboulton/php-resque/resque.php';