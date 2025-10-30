<?php

namespace App\Providers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Response::macro('sse', function () {
            if ($json = $this->json()) {
                return $json;
            }

            $body = $this->body();

            $lines = explode("\n", $body);

            $jsonData = [];

            foreach ($lines as $line) {
                $line = trim($line);

                // Ignore non-data lines and empty chunks
                if (str_starts_with($line, 'data:')) {
                    $payload = trim(substr($line, 5)); // remove "data: "
                    if ($payload !== '') {
                        $jsonData[] = json_decode($payload, true);
                    }
                }
            }

            // Get last event or all events
            $last = end($jsonData);

            return $last;
        });
    }
}
