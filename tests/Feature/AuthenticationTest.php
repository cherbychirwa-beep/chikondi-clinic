<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LoginAttempt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    protected function login(string $username, string $password)
    {
        return $this->post(route('login.attempt'), ['username' => $username, 'password' => $password]);
    }

    public function test_staff_can_log_in_with_valid_credentials(): void
    {
        $user = $this->staff('receptionist', ['username' => 'rec1']);

        $this->login('rec1', 'Password123')->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'auth.login']);
        $this->assertDatabaseHas('login_attempts', ['username' => 'rec1', 'was_successful' => true]);
    }

    public function test_invalid_credentials_are_rejected_and_recorded(): void
    {
        $this->staff('receptionist', ['username' => 'rec1']);

        $this->login('rec1', 'wrong-password')->assertSessionHasErrors('username');

        $this->assertGuest();
        $this->assertDatabaseHas('login_attempts', ['username' => 'rec1', 'was_successful' => false]);
    }

    public function test_unknown_user_cannot_log_in(): void
    {
        $this->login('nobody', 'Password123')->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_deactivated_account_cannot_log_in(): void
    {
        $this->staff('receptionist', ['username' => 'gone', 'is_active' => false]);

        $this->login('gone', 'Password123')->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_passwords_are_stored_hashed(): void
    {
        $user = $this->staff('receptionist');

        $this->assertNotSame('Password123', $user->getRawOriginal('password'));
        $this->assertTrue(Hash::check('Password123', $user->getRawOriginal('password')));
    }

    public function test_staff_can_log_out(): void
    {
        $user = $this->staff('attendant');

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'auth.logout']);
    }

    public function test_user_can_change_password(): void
    {
        $user = $this->staff('attendant');

        $this->actingAs($user)->post(route('password.change.update'), [
            'current_password' => 'Password123',
            'password' => 'NewPassw0rd1',
            'password_confirmation' => 'NewPassw0rd1',
        ])->assertRedirect(route('dashboard'));

        $this->assertTrue(Hash::check('NewPassw0rd1', $user->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.password_changed']);
    }

    public function test_password_change_rejects_wrong_current_password_and_weak_passwords(): void
    {
        $user = $this->staff('attendant');

        $this->actingAs($user)->post(route('password.change.update'), [
            'current_password' => 'not-my-password', 'password' => 'NewPassw0rd1', 'password_confirmation' => 'NewPassw0rd1',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->post(route('password.change.update'), [
            'current_password' => 'Password123', 'password' => 'short', 'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('Password123', $user->fresh()->password));
    }

    public function test_first_login_forces_a_password_change_before_anything_else(): void
    {
        $user = $this->staff('receptionist', ['must_change_password' => true]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('password.change'));
        $this->actingAs($user)->get(route('patients.index'))->assertRedirect(route('password.change'));
        $this->actingAs($user)->get(route('password.change'))->assertOk();

        $this->actingAs($user)->post(route('password.change.update'), [
            'current_password' => 'Password123', 'password' => 'FreshPass123', 'password_confirmation' => 'FreshPass123',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse($user->fresh()->must_change_password);
        $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk();
    }

    public function test_account_is_locked_after_repeated_failed_attempts(): void
    {
        $this->staff('receptionist', ['username' => 'locked1']);

        foreach (range(1, config('chikondi.max_login_attempts')) as $_) {
            $this->login('locked1', 'bad-password')->assertSessionHasErrors('username');
        }

        // Even the correct password is refused while locked.
        $response = $this->login('locked1', 'Password123');
        $response->assertSessionHasErrors('username');
        $this->assertStringContainsString('Too many failed', session('errors')->first('username'));
        $this->assertGuest();
    }

    public function test_lockout_expires_after_the_configured_period(): void
    {
        $this->staff('receptionist', ['username' => 'locked2']);

        foreach (range(1, config('chikondi.max_login_attempts')) as $_) {
            LoginAttempt::create([
                'username' => 'locked2', 'ip_address' => '127.0.0.1', 'was_successful' => false,
                'attempted_at' => now()->subMinutes(config('chikondi.lockout_minutes') + 5),
            ]);
        }

        $this->login('locked2', 'Password123')->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_inactive_session_expires(): void
    {
        $user = $this->staff('receptionist');
        $stale = time() - (config('chikondi.session_timeout') * 60) - 60;

        $this->actingAs($user)->withSession(['last_activity' => $stale])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_active_session_is_kept_alive(): void
    {
        $user = $this->staff('receptionist');

        $this->actingAs($user)->withSession(['last_activity' => time() - 60])
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_session_timeout_is_configurable(): void
    {
        config(['chikondi.session_timeout' => 1]);
        $user = $this->staff('receptionist');

        $this->actingAs($user)->withSession(['last_activity' => time() - 120])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_account_deactivated_mid_session_is_signed_out(): void
    {
        $user = $this->staff('receptionist');
        $user->update(['is_active' => false]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_login_actions_are_written_to_the_audit_log(): void
    {
        $this->staff('receptionist', ['username' => 'audited']);
        $this->login('audited', 'Password123');

        $this->assertSame(1, AuditLog::where('action', 'auth.login')->count());
    }
}
