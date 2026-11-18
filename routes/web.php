<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get("/", function () {
    return view("welcome");
});

if (app()->environment('testing')) {
    Route::post('/test/login/{user}', function (User $user) {
        abort_unless(app()->environment('testing'), 404);

        Auth::guard('web')->login($user);

        return response()->noContent();
    })->name('repro.login');

    Route::post('/test/switch-user/{user}', function (Request $request, User $user) {
        abort_unless(app()->environment('testing'), 404);

        $previousUserId = $request->user()->id;
        Auth::guard('web')->login($user);

        return ['previous_user_id' => $previousUserId, 'user_id' => Auth::id()];
    })->middleware('auth:web')->name('repro.switch-user');
}
