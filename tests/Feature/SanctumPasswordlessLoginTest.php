<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    // This is only relevant for apps that have optional `password` on a user.
    Schema::table('users', function (Blueprint $table) {
        $table->string('password')->nullable()->change();
    });

    config(['sanctum.stateful' => ['localhost']]);
    $this->withHeaders(['Referer' => 'http://localhost', 'Accept' => 'application/json']);
});

test('a passwordless user remains authenticated after switching users', function () {
    $cookieName = config('session.cookie');
    $passwordedUser = User::factory()->create();
    $passwordlessUser = User::factory()->create(['password' => null]);

    // Step 1: Login the user. Normally using email + password for example.
    $login = $this->post(route('repro.login', $passwordedUser))
        ->assertNoContent()
        ->assertCookie($cookieName)
        ->assertSessionHas('password_hash_web');

    // Test only: Make sure our request sends the cookie with it
    $this->withCookie($cookieName, $login->getCookie($cookieName)->getValue());

    // Test only: Reset in-memory test state so it represents are more realistic scenario
    Auth::forgetGuards();
    session()->flush();

    // Assert that when sending the cookies, we're still logged in as passwordedUser.
    $this->get(route('repro.user'))
        ->assertOk()
        ->assertJsonPath('id', $passwordedUser->id);

    Auth::forgetGuards();
    session()->flush();

    // Step 2: Switch to a user without a password
    // (e.g. Nova impersonation to a user that only uses magic links)
    // Simply a `Auth::guard('web')->login($user);` call.
    $switch = $this->post(route('repro.switch-user', $passwordlessUser))
        ->assertOk()
        ->assertJsonPath('previous_user_id', $passwordedUser->id)
        ->assertJsonPath('user_id', $passwordlessUser->id);

    // Test only: Use the newly generated cookies
    $this->withCookie($cookieName, $switch->getCookie($cookieName)->getValue());
    Auth::forgetGuards();
    session()->flush();

    // Assert that we're logged in (Sanctum says 401)
    $this->get(route('repro.user'))
        ->assertOk()
        ->assertJsonPath('id', $passwordlessUser->id);
});
