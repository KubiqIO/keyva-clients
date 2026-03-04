<?php

namespace Keyva\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class KeyvaException extends Exception
{
    private ?int $statusCode;
    private ?ResponseInterface $response;

    public function __construct(string $message, ?int $statusCode = null, ?ResponseInterface $response = null)
    {
        parent::__construct($message, $statusCode ?? 0);
        $this->statusCode = $statusCode;
        $this->response = $response;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
