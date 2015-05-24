<?php

namespace Tmv\Diactoros\Amqp\Rpc;

use Psr\Http\Message\ResponseInterface;

class Response
{
    /**
     * @var ResponseInterface
     */
    protected $response;
    /**
     * @var bool
     */
    protected $callNext = false;

    /**
     * Response constructor.
     *
     * @param ResponseInterface $response
     * @param bool $callNext
     */
    public function __construct(ResponseInterface $response, $callNext = false)
    {
        $this->response = $response;
        $this->callNext = $callNext;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface $response
     * @return $this
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isCallNext()
    {
        return $this->callNext;
    }

    /**
     * @param boolean $callNext
     * @return $this
     */
    public function setCallNext($callNext)
    {
        $this->callNext = $callNext;
        return $this;
    }
}
