<?php
// vim: sw=4:ts=4:et:sta:

namespace alexsalt\tasks;

use Yii;
use Exception;

/**
 * worker to process task from a queue
 */
class TaskWorker extends AbstractWorker
{
    /**
     * run task from a message object, requeue on error
     */
    public function processMessage(BaseMessage $msg)
    {
        $task = $msg->object;
        if (!$task instanceof TaskInterface) {
            Yii::error([
                'msg' => 'TaskWorker: object does not implement TaskInterface',
                'data' => [
                    'classname' => get_class($task),
                    'info' => var_export($task, true),
                ],
            ]);
            return $this->drop($msg);
        }

        try {
            $task->run();
            return $this->ack($msg);
        } catch (Exception $e) {
            Yii::$app->errorHandler->logException($e);
            return $this->nack($msg);
        }
    }
}
