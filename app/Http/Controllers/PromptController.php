<?php

namespace App\Http\Controllers;

use App\Services\McpRequest\McpRequest;
use Illuminate\Http\Request;

class PromptController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return inertia('prompts/Index', [
            'data' => inertia()->defer(
                fn () => McpRequest::listPrompts()
            ),

            'result' => inertia()->optional(
                fn () => McpRequest::getPrompt(
                    $request->input('name'),
                    $request->input('arguments', [])
                )
            ),
        ]);
    }
}
