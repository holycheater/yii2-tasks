<?php
// vim: sw=4:ts=4:et:sta:

namespace alexsalt\tasks;

use alexsalt\amqp\MessageInterface;
use yii\base\Object;
use Serializable;

/**
 * basic message for running background tasks through TaskQueueComponent
 */
class TaskMessage extends BaseMessage implements Serializable
{
    /**
     * @var object
     */
    public $object;

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize($this->object);
    }

    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        $this->object = unserialize($data);
    }
}
