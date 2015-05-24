<?php

namespace Tmv\Diactoros\Amqp;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Tmv\Diactoros\Amqp\Rpc\Request as RpcRequest;
use Tmv\Diactoros\Amqp\Rpc\Response as RpcResponse;
use Tmv\Diactoros\Amqp\Rpc\Request\Serializer as RpcRequestSerializer;
use Tmv\Diactoros\Amqp\Rpc\Response\Serializer as RpcResponseSerializer;

class RpcClient
{
    /**
     * @var AMQPChannel
     */
    protected $channel;
    /**
     * @var string
     */
    protected $callbackQueue;
    /**
     * @var string
     */
    protected $correlationId;
    /**
     * @var string
     */
    protected $response;
    /**
     * @var string
     */
    protected $queueName = 'diactoros-rpc';
    /**
     * @var callable
     */
    protected $correlationIdGenerator;

    /**
     * RpcClient constructor.
     *
     * @param AMQPChannel $channel
     * @param string $queueName
     */
    public function __construct(AMQPChannel $channel, $queueName = 'diactoros-rpc')
    {
        $this->channel = $channel;
        $this->queueName = $queueName;

        $this->setCorrelationIdGenerator(function () {
            return uniqid(gethostname(), true);
        });

        $queue = $this->channel->queue_declare('', false, false, true, true);
        list($this->callbackQueue, ,) = $queue;
        $this->channel->basic_consume($this->callbackQueue, '', false, false, false, false, [$this, 'onResponse']);
    }

    /**
     * @return callable
     */
    public function getCorrelationIdGenerator()
    {
        return $this->correlationIdGenerator;
    }

    /**
     * @param callable $correlationIdGenerator
     * @return $this
     */
    public function setCorrelationIdGenerator($correlationIdGenerator)
    {
        if (!is_callable($correlationIdGenerator)) {
            throw new \InvalidArgumentException('Correlation ID generator is not callable');
        }
        $this->correlationIdGenerator = $correlationIdGenerator;
        return $this;
    }

    /**
     * @return mixed
     */
    protected function generateCorrelationId()
    {
        $generator = $this->getCorrelationIdGenerator();
        return $generator();
    }

    /**
     * @param AMQPMessage $message
     */
    public function onResponse(AMQPMessage $message) {
        if ((string)$message->get('correlation_id') === (string)$this->correlationId) {
            $this->response = $message->body;
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @return RpcResponse
     */
    public function request(ServerRequestInterface $request, ResponseInterface $response) {
        $this->response = null;
        $this->correlationId = $this->generateCorrelationId();

        $rpcRequest = new RpcRequest($request, $response);
        $data = RpcRequestSerializer::toArray($rpcRequest);

        $msg = new AMQPMessage(
            json_encode($data),
            ['correlation_id' => $this->correlationId, 'reply_to' => $this->callbackQueue]
        );
        $this->channel->basic_publish($msg, '', $this->queueName);
        while (!$this->response) {
            $this->channel->wait(null, null, 30);
        }

        $ret = json_decode($this->response, true);
        return RpcResponseSerializer::fromArray($ret);
    }
}
