<?php

namespace App\Http\Controllers;

use App\Services\McpRequest\McpRequest;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return inertia('Dashboard', [
            'tools' => inertia()->defer(fn () => McpRequest::listTools()),
            'prompts' => inertia()->defer(fn () => McpRequest::listPrompts()),
            'resources' => inertia()->defer(fn () => McpRequest::listResources()),
        ]);
    }
}
