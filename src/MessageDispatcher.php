<?php
/*
 * This file is part of the prooph/php-service-bus.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 16.03.14 - 13:21
 */

namespace Prooph\ServiceBus\Message\PhpResque;

use Prooph\Common\Messaging\RemoteMessage;
use Prooph\ServiceBus\Message\RemoteMessageDispatcher;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;

/**
 * Class MessageDispatcher
 *
 * @package Prooph\ServiceBus\Message\PhpResque
 * @author Alexander Miertsch <contact@prooph.de>
 */
class MessageDispatcher implements RemoteMessageDispatcher
{
    /**
     * @var string
     */
    protected $receiverJobClass = 'Prooph\ServiceBus\Message\PhpResque\MessageConsumerJob';

    protected $queue = "ginger-message-queue";

    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * @var bool
     */
    protected $trackStatus = false;

    /**
     * @param null|array $options
     */
    public function __construct(array $options = null)
    {
        if (is_array($options)) {
            if (isset($options['receiver_job_class']) && is_string($options['receiver_job_class'])) {
                $this->receiverJobClass = $options['receiver_job_class'];
            }

            if (isset($options['track_job_status'])) {
                $this->trackStatus = (bool)$options['track_job_status'];
            }

            if (isset($options['queue']) && is_string($options['queue'])) {
                $this->queue = $options['queue'];
            }
        }
    }

    /**
     * @param RemoteMessage $message
     * @return void
     */
    public function dispatch(RemoteMessage $message)
    {
        $this->events()->trigger(__FUNCTION__ . '.pre', $this, array('message' => $message));

        $payload = array(
            'message_class' => get_class($message),
            'message_data'  => $message->toArray()
        );

        $jobId = \Resque::enqueue($this->queue, $this->receiverJobClass, $payload, $this->trackStatus);

        $this->events()->trigger(
            __FUNCTION__ . '.post',
            $this,
            array('message' => $message, 'jobId' => $jobId)
        );
    }

    /**
     * @return EventManagerInterface
     */
    public function events()
    {
        if (is_null($this->events)) {
            $this->events = new EventManager(array(
                'prooph_message_dispatcher',
                __CLASS__
            ));
        }

        return $this->events;
    }

    /**
     * @return void
     */
    public function activateJobTracking()
    {
        $this->trackStatus = true;
    }

    /**
     * @return void
     */
    public function deactivateJobTracking()
    {
        $this->trackStatus = false;
    }

    /**
     * @param $queue
     * @throws \InvalidArgumentException
     */
    public function useQueue($queue)
    {
        if (!is_string($queue)) {
            throw new \InvalidArgumentException("Invalid queue provided. Needs to be a string");
        }
        $this->queue = $queue;
    }
}
 