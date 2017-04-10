# yii2-tasks
Component for running background tasks inside a `react/event-loop`.

## Install
```sh
composer require alex-salt/yii2-amqp
```

## Usage

### Configure application:
```php
return [
    'components' => [
        'rmq' => [
            'class' => \alexsalt\amqp\Connection::class,
            'host' => 'localhost',
        ],
        'tasks' => [
            'class' => \alexsalt\tasks\TaskQueueComponent::class,
            'rmq' => 'rmq',
            'defaultQueue' => 'tasks',
        ],
    ],
];
```

### Create a task

```php
use alexsalt\tasks\TaskInterface;

class MyTask implements TaskInterface {
    public $a;

    public $b = 1;

    public function run() {
        // your magic code
    }
}
```

### Send a task:
```php
$task = new MyTask([ 'a' => 1, 'b' => 5 ]);
Yii::$app->tasks->send($task);
```

### A task worker:
```php
use alexsalt\tasks\TaskWorker;

class WorkerController extends \yii\console\Controller {
    public function actionMyTask() {
        $worker = new TaskWorker([
            'queue' => 'tasks',
        ]);
        $worker->start();
    }
}
```

### Custom worker

```php
use alexsalt\tasks\BaseMessage;
use alexsalt\tasks\AbstractWorker;

// declare message class with structure
class MyMessage extends BaseMessage {
    public $a;

    public $b;
}

// declare worker with our handler
class MyWorker extends AbstractWorker {
    /**
     * @param MyMessage $msg
     */
    public function processMessage(BaseMessage $msg) {
        if ($msg->a - $msg->b) {
            return $this->ack($msg);
        } else if (!$msg->b) {
            Yii::error('dont like the message');
            return $this->drop($msg);
        } else {
            return $this->nack($msg);
        }
    }
}

// create and send message
$msg = new MyMessage([ 'a' => 1 ]);
Yii::$app->rmq->sendToQueue($msg, 'my-queue');

// start worker that does stuff
$worker = new MyWorker([ 'queue' => 'my-queue' ]);
$worker->start();
```
