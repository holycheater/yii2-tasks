<?php
// vim: sw=4:ts=4:et:sta:

use PHPUnit\Framework\TestCase;
use yii\di\Container;
use yii\web\Application;
use alexsalt\tasks\TaskQueueComponent;
use alexsalt\tasks\TaskMessage;
use alexsalt\tasks\TaskInterface;
use alexsalt\amqp\Connection;

class TaskQueueComponentTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        Yii::$container = new Container;

        $rmqMock = $this->createMock(Connection::class);
        $cfg = [
            'class' => Application::class,
            'id' => 'test-app',
            'name' => 'test',
            'basePath' => dirname(__DIR__),
            'components' => [
                'rmq' => $rmqMock,
            ],
        ];
        Yii::createObject($cfg);
    }

    public function tearDown()
    {
        parent::tearDown();
        Yii::$app = null;
        Yii::$container = null;
    }

    public function testSend()
    {
        $task = new StubTask;
        $msg = new TaskMessage([ 'object' => $task ]);

        $rmqMock = Yii::$app->get('rmq');
        $rmqMock->expects($this->once())
            ->method('sendToQueue')
            ->with($msg, 'tasks');

        $component = new TaskQueueComponent;
        $component->send($task);
    }
}

class StubTask implements TaskInterface {
    public $somevar;

    public function run()
    {
    }
}
