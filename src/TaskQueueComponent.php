<?php
// vim: sw=4:ts=4:et:sta:

namespace alexsalt\tasks;

use Yii;
use yii\base\Component;
use yii\base\Exception;

class TaskQueueComponent extends Component
{
    public $defaultQueue = 'tasks';

    public $rmq = 'rmq';

    public function send($object, $queue = null)
    {
        if (!$object instanceof TaskInterface) {
            throw new Exception('Not a task: ' . get_class($object));
        }

        $queue = $queue !== null ? $queue : $this->defaultQueue;

        $message = new TaskMessage([
            'object' => $object,
        ]);

        $rmq = Yii::$app->get($this->rmq);
        $rmq->sendToQueue($message, $queue);
    }
}
