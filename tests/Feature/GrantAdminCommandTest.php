<?php

use App\Models\RoleChangeAudit;
use App\Models\User;

test('it makes the account an admin and logs the change', function () {
    $user = User::factory()->create(['email' => 'player@example.com']);
    $previousRoleId = $user->role_id;

    $this->artisan('grant:admin', ['email' => 'player@example.com'])
        ->expectsOutputToContain('is now an admin')
        ->assertSuccessful();

    $user->refresh();

    expect($user->isAdmin())->toBeTrue();

    $audit = RoleChangeAudit::query()->sole();

    expect($audit->target_user_id)->toBe($user->id)
        ->and($audit->actor_user_id)->toBe($user->id)
        ->and($audit->old_role_id)->toBe($previousRoleId)
        ->and($audit->new_role_id)->toBe($user->role_id);
});

test('it fails when no user has the given email', function () {
    $this->artisan('grant:admin', ['email' => 'missing@example.com'])
        ->expectsOutputToContain('No user found')
        ->assertFailed();
});

test('it leaves an existing admin unchanged', function () {
    User::factory()->admin()->create(['email' => 'admin@example.com']);

    $this->artisan('grant:admin', ['email' => 'admin@example.com'])
        ->expectsOutputToContain('already an admin')
        ->assertSuccessful();

    expect(RoleChangeAudit::query()->count())->toBe(0);
});
