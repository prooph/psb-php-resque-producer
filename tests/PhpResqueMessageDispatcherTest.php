<?php
/*
 * This file is part of the prooph/php-service-bus.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 16.03.14 - 18:55
 */

namespace Prooph\ServiceBusTest\Message\PhpResque;

use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\InvokeStrategy\ForwardToRemoteMessageDispatcherStrategy;
use Prooph\ServiceBus\InvokeStrategy\HandleCommandStrategy;
use Prooph\ServiceBus\Message\FromRemoteMessageTranslator;
use Prooph\ServiceBus\Message\PhpResque\MessageDispatcher;
use Prooph\ServiceBus\Message\ProophDomainMessageToRemoteMessageTranslator;
use Prooph\ServiceBus\Router\CommandRouter;
use Prooph\ServiceBus\StaticBusRegistry;
use Prooph\ServiceBusTest\Mock\FileRemover;
use Prooph\ServiceBusTest\Mock\RemoveFileCommand;
use Prooph\ServiceBusTest\TestCase;
use Zend\EventManager\EventInterface;

/**
 * Class PhpResqueMessageDispatcherTest
 *
 * @package Prooph\ServiceBusTest\Message\PhpResque
 * @author Alexander Miertsch <contact@prooph.de>
 */
class PhpResqueMessageDispatcherTest extends TestCase
{
    protected $testFile;

    protected $orgDirPermissions;

    protected function setUp()
    {
        $this->testFile = __DIR__ . '/delete-me.txt';

        $this->orgDirPermissions = fileperms(__DIR__);

        chmod(__DIR__, 0770);

        file_put_contents($this->testFile, 'I am just a testfile. You can delete me.');

        $consumerCommandBus = new CommandBus();

        $consumerCommandBus->utilize(new FromRemoteMessageTranslator());

        $consumerCommandBus->utilize(new CommandRouter([
            'Prooph\ServiceBusTest\Mock\RemoveFileCommand' => new FileRemover()
        ]));

        $consumerCommandBus->utilize(new HandleCommandStrategy());

        StaticBusRegistry::setCommandBus($consumerCommandBus);

        StaticBusRegistry::setEventBus(new EventBus());
    }

    protected function tearDown()
    {
        StaticBusRegistry::reset();

        @unlink($this->testFile);

        chmod(__DIR__, $this->orgDirPermissions);
    }

    /**
     * @test
     */
    public function it_sends_remove_file_command_to_file_remover_via_php_resque()
    {
        $this->assertTrue(file_exists($this->testFile));

        $commandBus = new CommandBus();

        $commandRouter = new CommandRouter();

        $messageDispatcher = new MessageDispatcher(['track_job_status' => true, 'queue' => 'php-resque-test-queue']);

        $commandRouter->route('Prooph\ServiceBusTest\Mock\RemoveFileCommand')
            ->to($messageDispatcher);

        $commandBus->utilize($commandRouter);

        $commandBus->utilize(new ForwardToRemoteMessageDispatcherStrategy(new ProophDomainMessageToRemoteMessageTranslator()));

        $jobId = null;

        $messageDispatcher->events()->attach(
            'dispatch.post',
            function (EventInterface $e) use (&$jobId) {
                $jobId = $e->getParam('jobId');
            }
        );

        $removeFile = RemoveFileCommand::fromPayload($this->testFile);

        $commandBus->dispatch($removeFile);

        $this->assertNotNull($jobId);

        $status = new \Resque_Job_Status($jobId);

        $this->assertEquals(\Resque_Job_Status::STATUS_WAITING, $status->get());

        $worker = new \Resque_Worker(array('php-resque-test-queue'));

        $worker->logLevel = 1;

        $worker->work(0);

        $worker->shutdown();

        $this->assertEquals(\Resque_Job_Status::STATUS_COMPLETE, $status->get());

        $this->assertFalse(file_exists($this->testFile));
    }
}
 