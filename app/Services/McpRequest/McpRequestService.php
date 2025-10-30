<?php

namespace App\Services\McpRequest;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class McpRequestService
{
    protected function http($method, array $params = []): Response
    {
        return Http::withHeaders([
            'Accept' => 'text/event-stream, application/json',
        ])->post('http://mcpauth.test/mcp', [
            'jsonrpc' => '2.0',
            'id' => uniqid('mcp_'),
            'method' => $method,
            'params' => $params,
        ]);
    }

    public function listTools(): array
    {
        return $this->http('tools/list')->sse();
    }

    public function callTool(string $name, array $params = []): array
    {
        return $this->http('tools/call', [
            'name' => $name,
            'arguments' => $params,
        ])->sse();
    }

    public function readResource(string $name): array
    {
        return $this->http('resources/read', [
            'uri' => $name,
        ])->sse();
    }

    public function ping(): array|bool
    {
        try {
            return $this->http('ping')->sse();
        } catch (\Throwable $th) {
            return false;
        }
    }
}
