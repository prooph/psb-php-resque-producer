<?php
/*
 * This file is part of the prooph/php-service-bus.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 16.03.14 - 22:37
 */
namespace {

    require_once '../../vendor/autoload.php';

    chdir(__DIR__);

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    include 'classes.php';

    use Prooph\ServiceBus\CommandBus;
    use Prooph\ServiceBus\Example\Resque\FileWriter;
    use Prooph\ServiceBus\Example\Resque\WriteLine;
    use Prooph\ServiceBus\InvokeStrategy\ForwardToRemoteMessageDispatcherStrategy;
    use Prooph\ServiceBus\Message\PhpResque\MessageDispatcher;
    use Prooph\ServiceBus\Message\ProophDomainMessageToRemoteMessageTranslator;
    use Prooph\ServiceBus\Router\RegexRouter;
    use Zend\EventManager\EventInterface;

    if (isset($_GET['write'])) {

        //We are on the write side, so the resque-sample-bus needs information of how to send a message
        $commandBus = new CommandBus();

        $messageDispatcher = new MessageDispatcher([
            'track_job_status' => true, //Activate php-resque job tracking
            'queue' => 'resque-sample'  //Set queue that will be used to push messages on, the worker needs to use same
                                        //queue name to pull messages @see start-worker.php
        ]);

        $commandBus->utilize(new RegexRouter([RegexRouter::ALL => $messageDispatcher]));

        $commandBus->utilize(new ForwardToRemoteMessageDispatcherStrategy(new ProophDomainMessageToRemoteMessageTranslator()));

        //The PhpResqueMessageDispatcher uses a Redis-Server to manage background jobs
        //We want to track the status of the job and therefor we use the event system of MessageDispatcher to capture the JobId
        //of a new created Job
        $jobId = null;

        //After the MessageDispatcher has done it's work, we capture the JobId with an EventListener
        $messageDispatcher->events()->attach(
            'dispatch.post',
            function (EventInterface $e) use (&$jobId) {
                $jobId = $e->getParam('jobId');
            }
        );

        //Prepare the Command
        $writeLine = WriteLine::fromPayload($_GET['write']);

        //...and send it to the message dispatcher via CommandBus
        $commandBus->dispatch($writeLine);

        echo 'Message is sent with JobId: ' . $jobId . '. You can check the status with '
            . strtok($_SERVER["REQUEST_URI"], '?') . '<b>?status=' . $jobId . '</b>';

    } elseif (isset($_GET['status'])) {
        $status = new \Resque_Job_Status($_GET['status']);

        switch($status->get()) {
            case \Resque_Job_Status::STATUS_WAITING:
                echo 'Status: waiting. If you did not start a worker yet, than open a console, and run: <b>php '
                    . __DIR__ . '/start-worker.php</b>';
                break;
            case \Resque_Job_Status::STATUS_RUNNING:
                echo 'Status: running. Wait a moment, the job should finish soon.';
                break;
            case \Resque_Job_Status::STATUS_COMPLETE:
                echo 'Status: complete. You should see a new line with your text, when you open: <b>'
                    . strtok($_SERVER["REQUEST_URI"], '?') . '</b>';
                break;
            case \Resque_Job_Status::STATUS_FAILED:
                echo "Status failed: Something went wrong. Stop current worker. Try again writing some text with: "
                    . strtok($_SERVER["REQUEST_URI"], '?') . "<b>?write=some text</b>' "
                    . "and start a new worker with this command: <b>VVERBOSE=1 php " . __DIR__ . "/start-worker.php</b>. "
                    . "You should be able to see the error which causes the job to fail.";
                break;
            default:
                echo "Job can not be found. Maybe you've passed an old or incomplete job id to the status param?";

        }

    } else {
        $fileWriter = new FileWriter(__DIR__ . '/dump.txt');

        echo "use '" . strtok($_SERVER["REQUEST_URI"], '?') . "<b>?write=some text</b>' to add new lines to the output"
            . "<br><br><font color='grey'>This sample requires a running <b>redis-server</b> and write access to: <b>" . __DIR__ . "</b></font><br><br>";
        echo nl2br($fileWriter->getContent());
    }
}