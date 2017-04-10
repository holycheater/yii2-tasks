<?php
// vim: sw=4:ts=4:et:sta:

use PHPUnit\Framework\TestCase;
use PhpAmqpLib\Message\AMQPMessage;
use alexsalt\tasks\BaseMessage;

class BaseMessageTest extends TestCase
{
    public function testSerialization()
    {
        $src = new StubMessage;
        $src->a = 123;

        $dst = unserialize($src->getPayload());
        $this->assertEquals(123, $dst->a);
    }

    public function testFromAmqp()
    {
        $src = new StubMessage;
        $src->a = 123;
        $amqp = new AMQPMessage($src->getPayload());

        $dst = BaseMessage::fromAmqp($amqp);
        $this->assertTrue($dst instanceof StubMessage);
        $this->assertEquals(123, $dst->a);
        $this->assertEquals($amqp, $dst->amqpMessage);
    }
}

class StubMessage extends BaseMessage
{
    public $a;
}
