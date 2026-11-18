<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

test('switching to a passwordless user does not retain the previous password hash', function () {
    $passwordedUser = User::factory()->make(['id' => 1]);
    $passwordlessUser = User::factory()->make(['id' => 2, 'password' => null]);
    $guard = Auth::guard('web');

    $guard->login($passwordedUser);
    $originalPasswordHash = session('password_hash_web');

    expect($originalPasswordHash)->not->toBeNull();

    $guard->login($passwordlessUser);

    expect(session($guard->getName()))->toBe($passwordlessUser->getAuthIdentifier());
    expect(session('password_hash_web'))->not->toBe($originalPasswordHash);
});
