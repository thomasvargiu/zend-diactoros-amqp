<?php

namespace Tmv\Diactoros\Amqp\Rpc\Response;

use Zend\Diactoros\Response\Serializer as ResponseSerializer;
use Tmv\Diactoros\Amqp\Rpc\Response as RpcResponse;

class Serializer
{
    /**
     * @param RpcResponse $response
     * @return array
     */
    public static function toArray(RpcResponse $response)
    {
        return [
            'response' => ResponseSerializer::toString($response->getResponse()),
            'call_next' => $response->isCallNext()
        ];
    }

    /**
     * @param array $array
     * @return RpcResponse
     */
    public static function fromArray(array $array)
    {
        return new RpcResponse(
            !empty($array['response']) ? ResponseSerializer::fromString($array['response']) : null,
            array_key_exists('call_next', $array) ? (bool)$array['call_next'] : false
        );
    }
}
