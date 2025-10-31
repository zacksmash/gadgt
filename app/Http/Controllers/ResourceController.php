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
            'data' => inertia()->defer(fn () => McpRequest::listResources()),
            'result' => inertia()->optional(fn () => McpRequest::readResource($request->input('name'))),
        ]);
    }
}
