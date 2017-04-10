<?php
// vim: sw=4:ts=4:et:sta:

namespace alexsalt\tasks;

use alexsalt\amqp\MessageInterface;
use PhpAmqpLib\Message\AMQPMessage;
use yii\base\Object;

abstract class BaseMessage extends Object implements MessageInterface
{
    /**
     * @var \PhpAmqpLib\Message\AMQPMessage
     */
    public $amqpMessage;

    /**
     * @inheritdoc
     */
    public function getPayload()
    {
        return serialize($this);
    }

    public static function fromAmqp(AMQPMessage $amqpMessage)
    {
        $msg = unserialize($amqpMessage->body);
        $msg->amqpMessage = $amqpMessage;
        return $msg;
    }
}
