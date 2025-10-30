<?php

namespace App\Services\McpRequest;

use Illuminate\Support\Facades\Facade;

class McpRequest extends Facade
{
    protected static function getFacadeAccessor()
    {
        return McpRequestFactory::class;
    }
}
