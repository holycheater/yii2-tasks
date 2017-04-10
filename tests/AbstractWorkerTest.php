<?php
// vim: sw=4:ts=4:et:sta:

use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use alexsalt\tasks\AbstractWorker;
use alexsalt\tasks\BaseMessage;

class AbstractWorkerTest extends TestCase
{
    public function testInit()
    {
        $worker = $this->getMockForAbstractClass(AbstractWorker::class);
        $this->assertTrue($worker->loop instanceof LoopInterface);
    }

    public function testOnAmqpMessage()
    {
        $worker = $this->getMockForAbstractClass(AbstractWorker::class);
        $msg = $this->getMockForAbstractClass(BaseMessage::class);
        $amsg = new AMQPMessage($msg->getPayload());

        $msgCheck = clone $msg;
        $msgCheck->amqpMessage = $amsg;

        $worker->expects($this->once())
            ->method('processMessage')
            ->with($msgCheck);

        $worker->onAmqpMessage($amsg);
    }

    public function testAck()
    {
        list($worker, $mockChannel) = $this->mockWorker();
        $msg = $this->getMockForAbstractClass(BaseMessage::class);
        $msg->amqpMessage = new AMQPMessage($msg->getPayload());
        $msg->amqpMessage->delivery_info = [ 'delivery_tag' => 'sometag' ];

        $mockChannel->expects($this->once())
            ->method('basic_ack')
            ->with('sometag');
        $worker->ack($msg);
    }

    public function testNack()
    {
        list($worker, $mockChannel) = $this->mockWorker();
        $msg = $this->getMockForAbstractClass(BaseMessage::class);
        $msg->amqpMessage = new AMQPMessage($msg->getPayload());
        $msg->amqpMessage->delivery_info = [ 'delivery_tag' => 'sometag' ];

        $mockChannel->expects($this->once())
            ->method('basic_reject')
            ->with('sometag', true);
        $worker->nack($msg);
    }

    public function testDrop()
    {
        list($worker, $mockChannel) = $this->mockWorker();
        $msg = $this->getMockForAbstractClass(BaseMessage::class);
        $msg->amqpMessage = new AMQPMessage($msg->getPayload());
        $msg->amqpMessage->delivery_info = [ 'delivery_tag' => 'sometag' ];

        $mockChannel->expects($this->once())
            ->method('basic_reject')
            ->with('sometag', false);
        $worker->drop($msg);
    }

    public function mockWorker()
    {
        $worker = $this->getMockForAbstractClass(AbstractWorker::class);
        $mockChan = $this->createMock(AMQPChannel::class);
        $this->setProtectedProperty($worker, 'channel', $mockChan);

        return [ $worker, $mockChan ];
    }

    public function setProtectedProperty($obj, $prop, $val)
    {
        $reflect = new \ReflectionClass($obj);
        $reflectProp = $reflect->getProperty($prop);
        $reflectProp->setAccessible(true);
        $reflectProp->setValue($obj, $val);
    }
}

class StubWorker
{
}
