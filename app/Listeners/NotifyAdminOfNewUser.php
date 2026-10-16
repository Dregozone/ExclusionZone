<?php

namespace App\Listeners;

use App\Mail\NewUserSignedUp;
use App\Mail\NewUserSignUpBurst;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotifyAdminOfNewUser
{
    private const BURST_THRESHOLD = 5;

    private const BURST_WINDOW_MINUTES = 10;

    private const CACHE_KEY = 'signup-notification-count';

    /**
     * Email the admin about the new player once the response has been sent, never failing sign-up.
     */
    public function handle(Registered $event): void
    {
        $address = config('app.admin_notify_email');

        if (blank($address) || ! $event->user instanceof User) {
            return;
        }

        $user = $event->user;
        $referrer = $this->referrer();
        $country = $this->country();

        defer(function () use ($address, $user, $referrer, $country): void {
            try {
                $this->notify($address, $user, $referrer, $country);
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }

    /**
     * Send the individual email, or a single summary once the burst threshold is passed.
     */
    private function notify(string $address, User $user, ?string $referrer, ?string $country): void
    {
        Cache::add(self::CACHE_KEY, 0, now()->addMinutes(self::BURST_WINDOW_MINUTES));
        $count = Cache::increment(self::CACHE_KEY);
        $totalPlayers = User::query()->count();

        if ($count <= self::BURST_THRESHOLD) {
            Mail::to($address)->send(new NewUserSignedUp(
                $user->name,
                $user->email,
                $user->created_at ?? now(),
                $totalPlayers,
                $referrer,
                $country,
            ));
        } elseif ($count === self::BURST_THRESHOLD + 1) {
            Mail::to($address)->send(new NewUserSignUpBurst(
                self::BURST_THRESHOLD,
                self::BURST_WINDOW_MINUTES,
                $totalPlayers,
            ));
        }
    }

    /**
     * The external page the player came from, ignoring links within this site.
     */
    private function referrer(): ?string
    {
        $referrer = request()->headers->get('referer');

        if (blank($referrer) || parse_url($referrer, PHP_URL_HOST) === request()->getHost()) {
            return null;
        }

        return $referrer;
    }

    /**
     * The visitor's country as reported by Cloudflare, when present.
     */
    private function country(): ?string
    {
        $country = request()->headers->get('CF-IPCountry');

        return blank($country) ? null : $country;
    }
}
