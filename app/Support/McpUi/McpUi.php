<?php

namespace App\Support\McpUi;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class McpUi
{
    public static function generateWidget(string $html, array|object $tool): ?string
    {
        if (! $html) {
            return null;
        }

        $id = str()->random(12);

        $output = Str::replace([
            '{toolInput}',
            '{toolOutput}',
            '{toolResponseMetadata}',
            '{widgetId}',
            '{html}',
        ], [
            json_encode($tool['toolInput'], JSON_PRETTY_PRINT) ?? null,
            json_encode($tool['toolOutput'], JSON_PRETTY_PRINT) ?? null,
            json_encode($tool['toolResponseMetadata'], JSON_PRETTY_PRINT) ?? null,
            $id,
            $html,
        ], File::get(storage_path('app/private/stubs/_template.html')));

        // remove all files in the directory
        Storage::disk('local')->deleteDirectory('widgets');
        Storage::disk('local')->makeDirectory('widgets');

        File::put(storage_path("app/private/widgets/template-{$id}.html"), $output);

        return route('widgets.openai', ['id' => $id]);
    }
}
