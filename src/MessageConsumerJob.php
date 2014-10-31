<?php
/*
 * This file is part of the prooph/php-service-bus.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 16.03.14 - 17:19
 */

namespace Prooph\ServiceBus\Message\PhpResque;

use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Exception\CommandDispatchException;
use Prooph\ServiceBus\Exception\EventDispatchException;
use Prooph\ServiceBus\Message\MessageHeader;
use Prooph\ServiceBus\StaticBusRegistry;

/**
 * Class MessageConsumerJob
 *
 * @package Prooph\ServiceBus\Message\PhpResque
 * @author Alexander Miertsch <contact@prooph.de>
 */
class MessageConsumerJob
{
    /**
     * @var CommandBus
     */
    protected $commandBus;

    /**
     * @var EventBus
     */
    protected $eventBus;

    /**
     * Setup the environment to perform a message
     *
     * This method is required by Php_Resque and is called before a worker calls the {@method perform}
     */
    public function setUp()
    {
        $this->commandBus = StaticBusRegistry::getCommandBus();
        $this->eventBus   = StaticBusRegistry::getEventBus();
    }

    /**
     * Perform a message
     */
    public function perform()
    {
        $messageClass = $this->args['message_class'];

        /* @var $message \Prooph\ServiceBus\Message\MessageInterface */
        $message = $messageClass::fromArray($this->args['message_data']);

        if ($message->header()->type() === MessageHeader::TYPE_COMMAND) {
            try {
                $this->commandBus->dispatch($message);
            } catch (CommandDispatchException $ex) {
                throw $ex->getPrevious();
            }

        } else {
            try {
                $this->eventBus->dispatch($message);
            } catch (EventDispatchException $ex) {
                throw $ex->getPrevious();
            }

        }
    }
}
