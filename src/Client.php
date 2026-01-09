<?php

namespace Keyva;

class Client
{
    private $apiKey;
    private $baseUrl;

    public function __construct($apiKey, $baseUrl = "https://api.keyva.io")
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
    }

    public function ping()
    {
        return "pong";
    }
}
