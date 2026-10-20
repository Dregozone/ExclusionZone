<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('it resets the password using the password argument', function () {
    $user = User::factory()->create(['email' => 'player@example.com']);

    $this->artisan('reset:password', ['email' => 'player@example.com', 'newPlainPassword' => 'new-secret-password'])
        ->expectsOutputToContain('Password updated')
        ->assertSuccessful();

    expect(Hash::check('new-secret-password', $user->fresh()->password))->toBeTrue();
});

test('it prompts for the password when it is omitted', function () {
    $user = User::factory()->create(['email' => 'player@example.com']);

    $this->artisan('reset:password', ['email' => 'player@example.com'])
        ->expectsQuestion('New password', 'prompted-secret-password')
        ->assertSuccessful();

    expect(Hash::check('prompted-secret-password', $user->fresh()->password))->toBeTrue();
});

test('it fails when no user has the given email', function () {
    $this->artisan('reset:password', ['email' => 'missing@example.com', 'newPlainPassword' => 'new-secret-password'])
        ->expectsOutputToContain('No user found')
        ->assertFailed();
});

test('it rejects an empty password and leaves the existing one unchanged', function () {
    $user = User::factory()->create(['email' => 'player@example.com']);
    $originalHash = $user->password;

    $this->artisan('reset:password', ['email' => 'player@example.com'])
        ->expectsQuestion('New password', '')
        ->assertFailed();

    expect($user->fresh()->password)->toBe($originalHash);
});
