<?php

namespace App\Services\McpRequest;

class McpRequestFactory
{
    public function __call($method, $args)
    {
        return (new McpRequestService)->$method(...$args);
    }
}
