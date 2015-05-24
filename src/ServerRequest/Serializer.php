<?php

namespace Tmv\Diactoros\Amqp\ServerRequest;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;

class Serializer
{
    /**
     * @param ServerRequestInterface $serverRequest
     * @return array
     */
    public static function toArray(ServerRequestInterface $serverRequest)
    {
        return [
            'server_params' => $serverRequest->getServerParams(),
            'uploaded_files' => $serverRequest->getUploadedFiles(),
            'uri' => (string) $serverRequest->getUri(),
            'method' => $serverRequest->getMethod(),
            'body' => $serverRequest->getBody()->getContents(),
            'headers' => $serverRequest->getHeaders()
        ];
    }

    /**
     * @param array $array
     * @return ServerRequest
     */
    public static function fromArray(array $array)
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write($array['body']);
        return new ServerRequest(
            $array['server_params'],
            $array['uploaded_files'],
            $array['uri'],
            $array['method'],
            $stream,
            $array['headers']
        );
    }
}
