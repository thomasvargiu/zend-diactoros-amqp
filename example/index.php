<?php

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

use Zend\Diactoros\Server;
use Tmv\Diactoros\Amqp\RpcClient;
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$rpcClient = new RpcClient($channel, 'rpc_queue');

// Using the createServer factory, providing it with the various superglobals:
$server = Server::createServer(
    function ($request, $response, $done) use ($rpcClient) {
        $rpcResponse = $rpcClient->request($request, $response);
        if ($rpcResponse->getResponse()) {
            return $rpcResponse->getResponse();
        }
        $done();
    },
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);
$server->listen();

$channel->close();
$connection->close();