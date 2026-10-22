<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActionPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_facility_changes_require_the_admins_current_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $facility = [
            'name' => 'Computer Lab',
            'building' => 'Engineering Hall',
            'type' => 'Laboratory',
            'capacity' => 24,
        ];

        $this->actingAs($admin)
            ->postJson(route('admin.facilities.store'), $facility)
            ->assertUnprocessable()
            ->assertJsonPath('errors.current_password.0', 'Enter your current password to confirm this admin action.');

        $this->postJson(route('admin.facilities.store'), $facility + ['current_password' => 'incorrect'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.current_password.0', 'Enter your current password to confirm this admin action.');

        $this->assertDatabaseCount('facilities', 0);

        $this->postJson(route('admin.facilities.store'), $facility + ['current_password' => 'password'])
            ->assertRedirect(route('admin.facilities.create'))
            ->assertSessionHas('status', 'Facility created successfully.');

        $this->assertDatabaseHas('facilities', ['name' => 'Computer Lab']);
    }

    public function test_login_and_signup_pages_do_not_render_password_visibility_controls(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('data-password-toggle')
            ->assertDontSee('Show password')
            ->assertDontSee('Hide password');

        $this->get(route('register'))
            ->assertOk()
            ->assertDontSee('data-password-toggle')
            ->assertDontSee('Show password')
            ->assertDontSee('Hide password');
    }

    public function test_admin_management_pages_show_password_confirmation_for_actions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('admin-action-password')
            ->assertSee('Confirm admin action');

        $this->get(route('admin.facilities.create'))
            ->assertOk()
            ->assertSee('admin-action-password');

        $this->get(route('admin.property-management.index'))
            ->assertOk()
            ->assertSee('admin-action-password');
    }
}
