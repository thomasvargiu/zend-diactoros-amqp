<?php

namespace TmvTest\Diactoros\Amqp;

use Tmv\Diactoros\Amqp\RpcClient;

class RpcClientTest extends \PHPUnit_Framework_TestCase
{

    public function testConstruct()
    {
        $channel = static::getMockBuilder('PhpAmqpLib\\Channel\\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();

        $channel->expects(static::once())
            ->method('queue_declare')
            ->with('', false, false, true, true)
            ->willReturn(['queue', 0, 0]);

        $channel->expects(static::once())
            ->method('basic_consume')
            ->with('queue', '', false, false, false, false, static::isType('callable'))
            ->willReturn(['queue', 0, 0]);

        $rpcClient = new RpcClient($channel, 'rpc');
        static::assertInternalType('callable', $rpcClient->getCorrelationIdGenerator());
    }
}
