<?php
// vim: sw=4:ts=4:et:sta:

namespace alexsalt\tasks;

use Yii;
use yii\base\Object;
use React\EventLoop\StreamSelectLoop;
use MKraemer\ReactPCNTL\PCNTL;
use PhpAmqpLib\Exception\AMQPIOWaitException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;

/**
 * base class for all queue workers
 */
abstract class AbstractWorker extends Object
{
    const WAIT_BASE = 0.005;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    public $loop;

    /**
     * @var string queue name to process
     */
    public $queue;

    /**
     * @var string
     */
    public $loopClass = StreamSelectLoop::class;

    public $connection = 'rmq';

    /**
     * @var integer msg prefetch count
     */
    public $qos = 2;

    /**
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    protected $channel;

    private $_wt;

    private $_idleSince;

    public function init()
    {
        parent::init();
        $classname = $this->loopClass;
        $this->loop = new $classname;

        $pcntl = new PCNTL($this->loop);
        $pcntl->on(SIGTERM, [ $this, 'onSigTerm' ]);
    }

    /**
     * start doing magic
     */
    public function start()
    {
        $this->channel = Yii::$app->get($this->connection)->channel(42);
        $this->channel->basic_qos(null, $this->qos, true);
        $this->channel->basic_consume($this->queue, 'worker', false, false, false, true, [ $this, 'onAmqpMessage' ]);
        $this->setWaitTimer(self::WAIT_BASE);
        $this->loop->addPeriodicTimer(30, [ $this, 'onIdle' ]);
        $this->loop->run();
    }

    /**
     * stop consuming messages
     */
    public function stop()
    {
        $this->channel->basic_cancel('worker');
        $this->loop->stop();
    }

    /**
     * wait for new data on amqp channel
     */
    public function waitChannel()
    {
        try {
            $this->channel->wait(null, true, self::WAIT_BASE);
        } catch (RuntimeException $e) {
            if ($e instanceof AMQPTimeoutException) {
                // just skip the timeout
                return;
            } elseif ($e instanceof AMQPIOWaitException) {
                // stream_select in AMQPReader::wait returned false, probably because we caught an interrupt signal from OS, skip
                return;
            } else {
                throw $e;
            }
        }
    }

    public function setWaitTimer($period)
    {
        /* drop old timer */
        if ($this->_wt !== null) {
            $this->_wt->cancel();
        }

        $this->_wt = $this->loop
            ->addPeriodicTimer($period, [ $this, 'waitChannel' ]);
    }

    /**
     * just stop worker on sigTERM
     */
    public function onSigTerm()
    {
        $this->stop();
    }

    /**
     * do stuff like close db conn when idle
     */
    public function onIdle()
    {
        if (time() - $this->_idleSince < 30) {
            return;
        }
        Yii::$app->db->close();
    }

    public function onAmqpMessage(AMQPMessage $amqpMessage)
    {
        $this->_idleSince = time();
        $msg = BaseMessage::fromAmqp($amqpMessage);
        $this->processMessage($msg);
    }

    /**
     * acknowledge message as processed
     */
    public function ack(BaseMessage $msg)
    {
        $tag = $msg->amqpMessage->delivery_info['delivery_tag'];
        $this->channel->basic_ack($tag);
    }

    /**
     * reject and requeue message
     */
    public function nack(BaseMessage $msg)
    {
        $tag = $msg->amqpMessage->delivery_info['delivery_tag'];
        $this->channel->basic_reject($tag, true);
    }

    /**
     * drop message from queue
     */
    public function drop(BaseMessage $msg)
    {
        $tag = $msg->amqpMessage->delivery_info['delivery_tag'];
        $this->channel->basic_reject($tag, false);
    }

    /**
     * @param BaseMessage $msg
     */
    abstract public function processMessage(BaseMessage $msg);
}
