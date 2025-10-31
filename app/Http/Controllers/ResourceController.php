<?php

namespace App\Http\Controllers;

use App\Services\McpRequest\McpRequest;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return inertia('resources/Index', [
            'data' => inertia()->defer(function () {
                return [
                    'resources' => McpRequest::listResources(),
                ];
            }),

            'result' => inertia()->optional(function () use ($request) {
                return McpRequest::readResource($request->input('name'));
            }),
        ]);
    }
}
