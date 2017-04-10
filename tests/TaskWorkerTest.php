<?php
// vim: sw=4:ts=4:et:sta:

use PHPUnit\Framework\TestCase;
use alexsalt\tasks\TaskMessage;
use alexsalt\tasks\TaskInterface;
use alexsalt\tasks\TaskWorker;

class TaskWorkerTest extends TestCase
{
    public function testProcessMessage()
    {
        $stubTask = $this->createMock(TaskInterface::class);
        $worker = $this->getMockBuilder(TaskWorker::class)
            ->setMethods([ 'ack' ])
            ->getMock();
        $msg = new TaskMessage([ 'object' => $stubTask ]);

        $stubTask->expects($this->once())
            ->method('run');
        $worker->expects($this->once())
            ->method('ack');

        $worker->processMessage($msg);
    }
}
