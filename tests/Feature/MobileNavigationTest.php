<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_layout_provides_accessible_mobile_navigation(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('mobile-menu-toggle', false)
            ->assertSee('mobile-navigation', false)
            ->assertSee('mobile-navigation-backdrop', false)
            ->assertSee('fixed bottom-0 left-0', false)
            ->assertSee('backdrop-blur-sm', false)
            ->assertSee('transition-transform duration-300', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('Calendar')
            ->assertSee('Log in');
    }

    public function test_authenticated_mobile_menu_includes_profile_notifications_and_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('reserve'))
            ->assertOk()
            ->assertSee('Profile settings')
            ->assertSee('Notifications')
            ->assertSee('Admin dashboard')
            ->assertSee('Log out');
    }

    public function test_calendar_pages_use_mobile_friendly_initial_views_and_layouts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('calendar'))
            ->assertOk()
            ->assertSee("matches ? 'listWeek' : 'timeGridWeek'", false)
            ->assertSee('calendar-page-layout', false);

        $this->get('/calendar/manage')
            ->assertOk()
            ->assertSee("matches ? 'dayGridMonth' : 'timeGridWeek'", false)
            ->assertSee('calendar-manage-layout', false);
    }
}
