<?php

use App\Http\Controllers\HelpdeskController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/helpdesk', [HelpdeskController::class, 'index'])
    ->name('helpdesk.index');

    Route::post('/helpdesk/threads', [HelpdeskController::class, 'storeThread'])
        ->name('helpdesk.store');

    Route::get('/helpdesk/threads/{thread}', [HelpdeskController::class, 'showThread'])
        ->name('helpdesk.thread');

    Route::post('/helpdesk/threads/{thread}/send', [HelpdeskController::class, 'sendMessage'])
        ->name('helpdesk.send');

    Route::post('/helpdesk/threads/{thread}/stream', [HelpdeskController::class, 'streamMessage'])
        ->name('helpdesk.stream');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});
