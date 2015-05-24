<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Tmv\Diactoros\Amqp\Rpc\Request as RpcRequest;
use Tmv\Diactoros\Amqp\Rpc\Request\Serializer as RpcRequestSerializer;


$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$emitter = new \Tmv\Diactoros\Amqp\Response\AmqpEmitter($channel, 'rpc_queue');


echo " [x] Awaiting RPC requests\n";
$callback = function($req) use ($emitter) {
    $emitter->setMessage($req);
    $rpcRequest = RpcRequestSerializer::fromArray(json_decode($req->body, true));
    echo " [x] New request\n";


    $server = new Zend\Diactoros\Server(
        function ($request, $response, $done) {
            return $response->getBody()->write("Hello world!");
        },
        $rpcRequest->getRequest(),
        $rpcRequest->getResponse()
    );
    $server->setEmitter($emitter);
    $server->listen();
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('rpc_queue', '', false, false, false, false, $callback);

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();