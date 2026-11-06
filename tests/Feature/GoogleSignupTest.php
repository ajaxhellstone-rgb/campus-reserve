<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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

    public function test_signup_rejects_passwords_missing_required_character_types(): void
    {
        foreach ([
            'lowercase1!' => 'The password must contain at least one uppercase letter.',
            'Uppercase!' => 'The password must contain at least one number.',
            'Uppercase1' => 'The password must contain at least one symbol.',
        ] as $password => $message) {
            $response = $this->post(route('register'), [
                'name' => 'Password Test User',
                'email' => 'password-test@example.com',
                'password' => $password,
                'password_confirmation' => $password,
            ]);

            $response->assertSessionHasErrors('password');
            $this->assertContains($message, session('errors')->getBag('default')->get('password'));
        }
    }

    public function test_login_and_signup_pages_do_not_offer_microsoft_sign_in(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Login with Microsoft')
            ->assertDontSee('/auth/microsoft');

        $this->get(route('register'))
            ->assertOk()
            ->assertDontSee('Sign up with Microsoft')
            ->assertDontSee('/auth/microsoft');
    }

    public function test_microsoft_oauth_endpoints_are_not_registered(): void
    {
        $this->get('/auth/microsoft')->assertNotFound();
        $this->get('/auth/microsoft/callback')->assertNotFound();
    }

    public function test_signup_page_shows_password_requirements_next_to_the_password_field(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Password requirements')
            ->assertSee('Use 5 to 30 characters.')
            ->assertSee('Include at least one uppercase letter.')
            ->assertSee('Include at least one number.')
            ->assertSee('Include at least one symbol.')
            ->assertSee('Enter the same password in the confirmation field.')
            ->assertSee('password-rule-uppercase')
            ->assertSee('password-rule-number')
            ->assertSee('password-rule-symbol')
            ->assertSee('aria-label="Not met"', false)
            ->assertSee("indicator.textContent = met ? '✓' : '×';", false)
            ->assertSee("passwordConfirmInput.addEventListener('input', updatePasswordUI);", false)
            ->assertSee('maxlength="30"', false)
            ->assertSee('minlength="5"', false);
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
