<?php

use App\Http\Controllers\PromptController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ToolController;
use App\Services\McpRequest\McpRequest;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Dashboard', [
        'canUpdateProfile' => Features::canUpdateProfileInformation(),
        'canUpdatePassword' => Features::enabled(Features::updatePasswords()),
        'canManageTwoFactorAuthentication' => Features::canManageTwoFactorAuthentication(),
        'confirmsTwoFactorAuthentication' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::match(['get', 'post'], 'tools', ToolController::class)->name('tools.index');
    Route::match(['get', 'post'], 'prompts', PromptController::class)->name('prompts.index');
    Route::match(['get', 'post'], 'resources', ResourceController::class)->name('resources.index');

    Route::match(['get', 'post'], 'ping', function () {
        return Inertia::render('ping/Index', [
            'pong' => inertia()->optional(fn () => McpRequest::ping()),
        ]);
    })->name('ping.index');

    Route::get('openai-widget/{id}', function ($id) {
        return File::get(storage_path("app/private/widgets/template-{$id}.html"));
    })->name('widgets.openai');
});
