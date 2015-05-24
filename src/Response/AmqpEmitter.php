<?php

namespace Tmv\Diactoros\Amqp\Response;

use PhpAmqpLib\Message\AMQPMessage;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\EmitterInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use Tmv\Diactoros\Amqp\Rpc\Request as RpcRequest;
use Tmv\Diactoros\Amqp\Rpc\Response as RpcResponse;
use Tmv\Diactoros\Amqp\Rpc\Response\Serializer as RpcResponseSerializer;

class AmqpEmitter implements EmitterInterface
{
    /**
     * @var AMQPChannel
     */
    protected $channel;
    /**
     * @var string
     */
    protected $queueName;
    /**
     * @var AMQPMessage
     */
    protected $message;

    /**
     * AmqpEmitter constructor.
     *
     * @param AMQPChannel $channel
     * @param string      $queueName
     */
    public function __construct(AMQPChannel $channel, $queueName)
    {
        $this->channel = $channel;
        $this->queueName = $queueName;

        $channel->queue_declare($queueName, false, false, false, false);
    }


    /**
     * Emit a response.
     *
     * Emits a response, including status line, headers, and the message body,
     * according to the environment.
     *
     * Implementations of this method may be written in such a way as to have
     * side effects, such as usage of header() or pushing output to the
     * output buffer.
     *
     * Implementations MAY raise exceptions if they are unable to emit the
     * response; e.g., if headers have already been sent.
     *
     * @param ResponseInterface $response
     */
    public function emit(ResponseInterface $response)
    {
        $req = $this->getMessage();

        if (!$req) {
            throw new \RuntimeException('No message provided for AmqpEmitter');
        }

        $rpcResponse = new RpcResponse($response);
        $msg = new AMQPMessage(
            json_encode(RpcResponseSerializer::toArray($rpcResponse)),
            array('correlation_id' => $req->get('correlation_id'))
        );

        $req->delivery_info['channel']->basic_publish(
            $msg, '', $req->get('reply_to'));
        $req->delivery_info['channel']->basic_ack(
            $req->delivery_info['delivery_tag']);

        $this->message = null;
    }

    /**
     * @return AMQPMessage
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param AMQPMessage $message
     * @return $this
     */
    public function setMessage(AMQPMessage $message)
    {
        $this->message = $message;
        return $this;
    }
}
