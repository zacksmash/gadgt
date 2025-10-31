<?php

namespace App\Http\Controllers;

use App\Services\McpRequest\McpRequest;
use App\Support\McpUi\McpUi;
use Illuminate\Http\Request;

class ToolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __invoke(Request $request)
    {
        return inertia('tools/Index', [
            'data' => inertia()->defer(fn () => McpRequest::listTools()),
            'result' => inertia()->optional(
                fn () => McpRequest::callTool(
                    $request->input('name'),
                    $request->input('params'),
                )
            ),
            'resource' => inertia()->optional(function () use ($request) {
                $response = McpRequest::readResource($request->input('name'));

                $widget = collect($response['result']['contents'] ?? [])
                    ->firstWhere('mimeType', 'text/html+skybridge');

                return [
                    'result' => $response,
                    'template' => McpUi::generateWidget($widget['text'], request()->input('tool')),
                ];
            }),
        ]);
    }
}
