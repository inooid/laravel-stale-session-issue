<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

if (app()->environment('testing')) {
    Route::get('/test/user', fn (Request $request) => ['id' => $request->user()->id])
        ->middleware([EnsureFrontendRequestsAreStateful::class, 'auth:sanctum'])->name('repro.user');
}
