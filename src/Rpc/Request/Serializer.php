<?php

namespace Tmv\Diactoros\Amqp\Rpc\Request;

use Tmv\Diactoros\Amqp\ServerRequest\Serializer as ServerRequestSerializer;
use Zend\Diactoros\Response\Serializer as ResponseSerializer;
use Tmv\Diactoros\Amqp\Rpc\Request as RpcRequest;

class Serializer
{
    /**
     * @param RpcRequest $request
     * @return array
     */
    public static function toArray(RpcRequest $request)
    {
        return [
            'request' => ServerRequestSerializer::toArray($request->getRequest()),
            'response' => ResponseSerializer::toString($request->getResponse())
        ];
    }

    /**
     * @param array $array
     * @return RpcRequest
     */
    public static function fromArray(array $array)
    {
        return new RpcRequest(
            ServerRequestSerializer::fromArray($array['request']),
            ResponseSerializer::fromString($array['response'])
        );
    }
}
