<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use OTPHP\TOTP;
use Tests\TestCase;

class ProfileSecuritySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_settings_script_initializes_the_password_and_authenticator_controls(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('function syncPhoneFieldsToForm()', false)
            ->assertSee('open-password-modal', false)
            ->assertSee('profile-name-input', false)
            ->assertSee(route('profile.name.update'), false)
            ->assertDontSee('Save settings')
            ->assertDontSee('>Cancel</a>', false)
            ->assertSee('profile-email-edit', false)
            ->assertSee('profile-email-save', false)
            ->assertSee(route('profile.email.update'), false)
            ->assertSee('Forgot password?', false)
            ->assertSee('Resend email', false)
            ->assertSee('resend-password-reset-link', false)
            ->assertSee('generate-authenticator-qr', false)
            ->assertSee('authenticator_otp', false)
            ->assertSee('AbortSignal.timeout(20000)', false)
            ->assertDontSee('name="setup_current_password" required', false)
            ->assertDontSee('name="authenticator_otp" required', false)
            ->assertSee('Finish authenticator setup and verify a code', false)
            ->assertDontSee(route('profile.password.verify'), false)
            ->assertSee("event.preventDefault();\n            const submitButton", false)
            ->assertDontSee('window.location.reload()', false)
            ->assertSee("this.textContent = 'Saving...'", false);
    }

    public function test_profile_settings_can_be_saved_without_a_page_redirect(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('profile.update'), [
                'name' => 'Updated Profile Name',
                'phone' => '+639123456789',
                'country_code' => '+63',
                'email_notifications' => true,
                'two_fa_enabled' => false,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'Profile updated successfully.')
            ->assertJsonPath('user.name', 'Updated Profile Name')
            ->assertJsonPath('user.phone', '+639123456789');

        $this->assertSame('Updated Profile Name', $user->fresh()->name);
        $this->assertSame('+639123456789', $user->fresh()->phone);
    }

    public function test_user_can_change_email_after_confirming_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'old.address@example.com',
            'password' => 'CurrentPass1!',
        ]);

        $this->actingAs($user)
            ->postJson(route('profile.email.update'), [
                'email' => 'new.address@example.com',
                'current_password' => 'CurrentPass1!',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'Email address updated successfully.')
            ->assertJsonPath('email', 'new.address@example.com');

        $this->assertSame('new.address@example.com', $user->fresh()->email);
        $this->assertSame(
            hash('sha256', strtolower('new.address@example.com')),
            $user->fresh()->email_hash
        );
    }

    public function test_profile_name_can_be_saved_automatically(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);

        $this->actingAs($user)
            ->postJson(route('profile.name.update'), ['name' => 'Updated Name'])
            ->assertOk()
            ->assertJsonPath('status', 'Name saved automatically.')
            ->assertJsonPath('name', 'Updated Name');

        $this->assertSame('Updated Name', $user->fresh()->name);
    }

    public function test_user_cannot_change_email_with_an_incorrect_password_or_to_an_existing_address(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'CurrentPass1!',
        ]);
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($user)
            ->postJson(route('profile.email.update'), [
                'email' => 'new.address@example.com',
                'current_password' => 'wrong-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->postJson(route('profile.email.update'), [
            'email' => 'taken@example.com',
            'current_password' => 'CurrentPass1!',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertSame('owner@example.com', $user->fresh()->email);
    }

    public function test_invalid_two_factor_disable_credentials_return_a_json_error(): void
    {
        $user = User::factory()->create([
            'password' => 'CurrentPass1!',
            'two_fa_enabled' => true,
            'two_fa_secret' => TOTP::create()->getSecret(),
        ]);

        $this->actingAs($user)
            ->postJson(route('profile.update'), [
                'name' => $user->name,
                'two_fa_enabled' => false,
                'disable_current_password' => 'wrong-password',
                'disable_2fa_otp' => '123456',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('field', 'disable_current_password')
            ->assertJsonPath('message', 'The current password is incorrect.');

        $this->assertTrue($user->fresh()->two_fa_enabled);
    }

    public function test_user_can_change_password_without_an_authenticator_code_when_two_factor_is_off(): void
    {
        Queue::fake();
        $user = User::factory()->create([
            'password' => 'CurrentPass1!',
        ]);

        $this->actingAs($user)
            ->postJson(route('profile.update'), [
                'name' => $user->name,
                'current_password' => 'CurrentPass1!',
                'password' => 'NewPass2!',
                'password_confirmation' => 'NewPass2!',
            ])
            ->assertOk()
            ->assertJsonPath('password_changed', true);

        $this->assertTrue(Hash::check('NewPass2!', $user->fresh()->password));
        Queue::assertPushed(SendQueuedNotifications::class);
    }

    public function test_user_can_start_authenticator_setup_without_immediately_enabling_two_factor(): void
    {
        $user = User::factory()->create([
            'password' => 'CurrentPass1!',
        ]);

        $this->actingAs($user)
            ->postJson(route('profile.2fa.setup'), [
                'setup_current_password' => 'CurrentPass1!',
            ])
            ->assertOk()
            ->assertJsonStructure([
                'authenticator_setup_uri',
                'authenticator_setup_qr',
                'authenticator_setup_secret',
            ]);

        $this->assertFalse($user->fresh()->two_fa_enabled);
        $this->assertNotEmpty(session('pending_authenticator_secret'));
    }

    public function test_authenticated_user_can_request_password_reset_link_to_account_email(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'profile.user@example.com',
        ]);

        $this->actingAs($user)
            ->postJson(route('profile.password.reset-link'))
            ->assertOk()
            ->assertJsonPath('status', 'A password reset email was queued for your account address. Check your inbox and spam folder shortly.');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'profile.user@example.com',
        ]);

        Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job): bool {
            return $job->notification instanceof PasswordResetLink
                && str_contains($job->notification->resetUrl, '/reset-password/')
                && str_contains($job->notification->resetUrl, 'email=profile.user%40example.com');
        });
        $notification = Queue::pushed(SendQueuedNotifications::class)[0]->notification;
        parse_str((string) parse_url($notification->resetUrl, PHP_URL_QUERY), $link);
        $link['token'] = basename((string) parse_url($notification->resetUrl, PHP_URL_PATH));
        $tokenHash = DB::table('password_reset_tokens')->where('email', 'profile.user@example.com')->value('token');
        $this->assertNotEmpty($tokenHash);
        $this->assertTrue(Hash::check($link['token'], $tokenHash));

        $this->get($notification->resetUrl)->assertOk();
        $this->post(route('password.update'), [
            'email' => $link['email'],
            'token' => $link['token'],
            'password' => 'ResetPass2!',
            'password_confirmation' => 'ResetPass2!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('ResetPass2!', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'profile.user@example.com',
        ]);
    }

    public function test_authenticator_setup_is_enabled_only_after_valid_code_is_saved(): void
    {
        $user = User::factory()->create([
            'password' => 'CurrentPass1!',
        ]);

        $this->actingAs($user)
            ->postJson(route('profile.2fa.setup'), [
                'setup_current_password' => 'CurrentPass1!',
            ])
            ->assertOk();

        $secret = session('pending_authenticator_secret');
        $this->post(route('profile.2fa.confirm'), [
            'authenticator_otp' => TOTP::createFromSecret($secret)->now(),
        ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Authenticator app 2FA is now enabled.');

        $this->assertTrue($user->fresh()->two_fa_enabled);
        $this->assertSame($secret, $user->fresh()->two_fa_secret);
        $this->assertNull(session('pending_authenticator_secret'));
    }

    public function test_authenticator_confirmation_can_complete_without_a_redirect(): void
    {
        $user = User::factory()->create([
            'password' => 'CurrentPass1!',
        ]);
        $secret = TOTP::create()->getSecret();

        $this->actingAs($user)
            ->withSession(['pending_authenticator_secret' => $secret])
            ->postJson(route('profile.2fa.confirm'), [
                'authenticator_otp' => TOTP::createFromSecret($secret)->now(),
            ])
            ->assertOk()
            ->assertJsonPath('status', 'Authenticator app 2FA is now enabled.');

        $this->assertTrue($user->fresh()->two_fa_enabled);
    }
}
