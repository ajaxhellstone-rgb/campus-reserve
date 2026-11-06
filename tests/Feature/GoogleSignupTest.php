<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleSignupTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_signup_form_prefills_the_google_name_and_email_over_stale_form_input(): void
    {
        $this->withSession([
            'pending_google_signup' => [
                'name' => 'Google User',
                'email' => 'google.user@example.com',
            ],
            '_old_input' => [
                'name' => 'Previous User',
                'email' => 'previous.user@example.com',
            ],
        ])
            ->get(route('register'))
            ->assertOk()
            ->assertSee('value="Google User"', false)
            ->assertSee('value="google.user@example.com"', false)
            ->assertDontSee('value="previous.user@example.com"', false);
    }

    public function test_new_google_users_can_complete_signup_with_their_own_password(): void
    {
        session()->put('pending_google_signup', [
            'name' => 'Google User',
            'email' => 'google.user@example.com',
        ]);

        $response = $this->post(route('register'), [
            'name' => 'Google User',
            'email' => 'google.user@example.com',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
        ]);

        $response->assertRedirect('/reserve');
        $this->assertTrue(Auth::check());
        $this->assertSame('google.user@example.com', Auth::user()->email);
        $this->assertDatabaseHas('users', [
            'email_hash' => hash('sha256', strtolower('google.user@example.com')),
        ]);
        $this->assertNull(session('pending_google_signup'));
    }

    public function test_microsoft_signup_form_prefills_the_microsoft_name_and_email(): void
    {
        $this->withSession([
            'pending_microsoft_signup' => [
                'name' => 'Microsoft User',
                'email' => 'microsoft.user@example.com',
            ],
        ])
            ->get(route('register'))
            ->assertOk()
            ->assertSee('value="Microsoft User"', false)
            ->assertSee('value="microsoft.user@example.com"', false);
    }

    public function test_new_microsoft_users_can_complete_signup_with_their_own_password(): void
    {
        session()->put('pending_microsoft_signup', [
            'name' => 'Microsoft User',
            'email' => 'microsoft.user@example.com',
        ]);

        $response = $this->post(route('register'), [
            'name' => 'Microsoft User',
            'email' => 'microsoft.user@example.com',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
        ]);

        $response->assertRedirect('/reserve');
        $this->assertTrue(Auth::check());
        $this->assertSame('microsoft.user@example.com', Auth::user()->email);
        $this->assertDatabaseHas('users', [
            'email_hash' => hash('sha256', strtolower('microsoft.user@example.com')),
        ]);
        $this->assertNull(session('pending_microsoft_signup'));
    }

    public function test_login_and_signup_pages_offer_microsoft_sign_in(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('auth.microsoft'), false)
            ->assertSee('Login with Microsoft');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee(route('auth.microsoft'), false)
            ->assertSee('Sign up with Microsoft');
    }

    public function test_signup_page_shows_password_requirements_next_to_the_password_field(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Password requirements')
            ->assertSee('Use 5 to 30 characters.')
            ->assertSee('Enter the same password in the confirmation field.')
            ->assertSee('maxlength="30"', false)
            ->assertSee('minlength="5"', false);
    }

    public function test_microsoft_sign_in_redirects_to_the_common_identity_endpoint(): void
    {
        config([
            'services.microsoft.client_id' => 'microsoft-client-id',
            'services.microsoft.client_secret' => 'microsoft-client-secret',
            'services.microsoft.redirect' => 'https://campusreserve.example/auth/microsoft/callback',
            'services.microsoft.tenant' => 'common',
        ]);

        $this->get(route('auth.microsoft'))
            ->assertRedirectContains('https://login.microsoftonline.com/common/oauth2/v2.0/authorize')
            ->assertRedirectContains('redirect_uri=https%3A%2F%2Fcampusreserve.example%2Fauth%2Fmicrosoft%2Fcallback');
    }

    public function test_microsoft_callback_starts_signup_for_a_new_user(): void
    {
        $socialiteUser = \Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getEmail')->once()->andReturn('microsoft.user@example.com');
        $socialiteUser->shouldReceive('getName')->once()->andReturn('Microsoft User');

        $microsoftDriver = \Mockery::mock();
        $microsoftDriver->shouldReceive('user')->once()->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('microsoft')
            ->andReturn($microsoftDriver);

        $this->get('/auth/microsoft/callback')
            ->assertRedirect(route('register'))
            ->assertSessionHas('pending_microsoft_signup', [
                'name' => 'Microsoft User',
                'email' => 'microsoft.user@example.com',
            ]);
    }

    public function test_user_phone_number_persists_when_updating_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Profile User',
            'email' => 'profile.user@example.com',
            'phone' => null,
        ]);

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => 'Profile User',
            'phone' => '+639171234567',
            'country_code' => '+63',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Profile updated successfully.');
        $this->assertSame('+639171234567', $user->fresh()->phone);
    }
}
