<?php

use App\Listeners\NotifyAdminOfNewUser;
use App\Mail\NewUserSignedUp;
use App\Mail\NewUserSignUpBurst;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());

    Mail::fake();
    Cache::flush();
    config(['app.admin_notify_email' => 'admin@example.com']);
});

function runDeferredSignUpCallbacks(): void
{
    app(DeferredCallbackCollection::class)->invoke();
}

/**
 * @param  array<string, string>  $headers
 */
function registerNewPlayer(string $email = 'new@example.com', array $headers = []): void
{
    test()->withHeaders($headers)->post(route('register.store'), [
        'name' => 'New Player',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    runDeferredSignUpCallbacks();
}

it('emails the admin when someone signs up', function () {
    registerNewPlayer(headers: [
        'Referer' => 'https://news.example.org/post',
        'CF-IPCountry' => 'GB',
    ]);

    Mail::assertSent(NewUserSignedUp::class, fn (NewUserSignedUp $mail) => $mail->hasTo('admin@example.com')
        && $mail->userEmail === 'new@example.com'
        && $mail->totalPlayers === User::count()
        && $mail->referrer === 'https://news.example.org/post'
        && $mail->country === 'GB'
        && $mail->envelope()->subject === 'New Exclusion Zone player: New Player');
});

it('sends nothing when no admin address is set', function () {
    config(['app.admin_notify_email' => '']);

    registerNewPlayer();

    Mail::assertNothingSent();
});

it('does not break registration when mail fails', function () {
    Exceptions::fake();
    Mail::shouldReceive('to')->andThrow(new RuntimeException('mail down'));

    registerNewPlayer();

    $this->assertAuthenticated();
    expect(User::where('email', 'new@example.com')->exists())->toBeTrue();
    Exceptions::assertReported(fn (RuntimeException $exception) => $exception->getMessage() === 'mail down');
});

it('sends a single summary after a burst of sign-ups', function () {
    foreach (range(1, 8) as $i) {
        (new NotifyAdminOfNewUser)->handle(new Registered(User::factory()->create()));
    }

    runDeferredSignUpCallbacks();

    Mail::assertSent(NewUserSignedUp::class, 5);
    Mail::assertSent(NewUserSignUpBurst::class, 1);
});

it('can build the resend mailer used in production', function () {
    config(['services.resend.key' => 're_test_key']);

    expect((new MailManager(app()))->mailer('resend')->getSymfonyTransport())
        ->toBeInstanceOf(ResendTransport::class);
});
