<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class GoogleSignupTest extends TestCase
{
    use RefreshDatabase;

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
