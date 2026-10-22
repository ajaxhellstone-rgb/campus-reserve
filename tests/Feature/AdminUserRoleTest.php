<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_admin_cannot_change_roles_and_does_not_see_user_actions(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);
        $member = User::factory()->create([
            'is_admin' => false,
            'is_super_admin' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.users.role', $member))
            ->assertDontSee(route('admin.users.destroy', $member));

        $this->actingAs($admin)
            ->post(route('admin.users.role', $member), ['role' => 'admin', 'current_password' => 'password'])
            ->assertForbidden();

        $this->assertFalse($member->fresh()->is_admin);
    }

    public function test_head_admin_can_promote_a_member_to_admin(): void
    {
        $headAdmin = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => true,
        ]);
        $member = User::factory()->create([
            'is_admin' => false,
            'is_super_admin' => false,
        ]);

        $this->actingAs($headAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.users.role', $member));

        $this->post(route('admin.users.role', $member), ['role' => 'admin', 'current_password' => 'password'])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('status', 'User role updated successfully.');

        $this->assertTrue($member->fresh()->is_admin);
    }

    public function test_head_admin_can_change_member_roles_without_a_redirect(): void
    {
        $headAdmin = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => true,
        ]);
        $member = User::factory()->create([
            'is_admin' => false,
            'is_super_admin' => false,
        ]);

        $this->actingAs($headAdmin)
            ->postJson(route('admin.users.role', $member), ['role' => 'admin', 'current_password' => 'password'])
            ->assertOk()
            ->assertJsonPath('status', 'User role updated successfully.');

        $this->assertTrue($member->fresh()->is_admin);
    }

    public function test_member_role_changes_require_the_admins_current_password(): void
    {
        $headAdmin = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => true,
        ]);
        $member = User::factory()->create([
            'is_admin' => false,
            'is_super_admin' => false,
        ]);

        $this->actingAs($headAdmin)
            ->postJson(route('admin.users.role', $member), ['role' => 'admin'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.current_password.0', 'Enter your current password to confirm this admin action.');

        $this->assertFalse($member->fresh()->is_admin);
    }
}