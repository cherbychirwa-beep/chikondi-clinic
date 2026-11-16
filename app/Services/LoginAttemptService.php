<?php

namespace App\Services;

use App\Models\LoginAttempt;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/** Records login attempts and enforces temporary lockout after repeated failures. */
class LoginAttemptService
{
    public function record(string $username, ?string $ip, bool $successful): void
    {
        LoginAttempt::create([
            'username' => $username,
            'ip_address' => $ip,
            'was_successful' => $successful,
            'attempted_at' => now(),
        ]);
    }

    /** Failures inside the lockout window that happened after the last successful login. */
    protected function activeFailures(string $username): Builder
    {
        $since = now()->subMinutes((int) config('chikondi.lockout_minutes'));
        $lastSuccess = LoginAttempt::where('username', $username)->where('was_successful', true)->max('attempted_at');

        $query = LoginAttempt::where('username', $username)
            ->where('was_successful', false)
            ->where('attempted_at', '>=', $since);

        if ($lastSuccess) {
            $query->where('attempted_at', '>', $lastSuccess);
        }

        return $query;
    }

    public function isLockedOut(string $username): bool
    {
        return $this->activeFailures($username)->count() >= (int) config('chikondi.max_login_attempts');
    }

    public function minutesRemaining(string $username): int
    {
        $oldest = $this->activeFailures($username)->min('attempted_at');
        if (! $oldest) {
            return 0;
        }

        $unlockAt = Carbon::parse($oldest)->addMinutes((int) config('chikondi.lockout_minutes'));

        return max(1, (int) ceil(now()->diffInMinutes($unlockAt, false)));
    }
}
