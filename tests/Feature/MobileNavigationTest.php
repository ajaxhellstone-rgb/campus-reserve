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

    public function test_primary_navigation_highlights_the_current_page_on_desktop_and_mobile(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([
            route('home') => '/',
            route('reserve') => '/reserve',
            route('calendar') => '/calendar',
            route('about') => '/about',
            route('contact') => '/contact',
        ] as $url => $path) {
            $response = $this->get($url)->assertOk();

            $this->assertSame(
                2,
                preg_match_all('/href="' . preg_quote($path, '/') . '"[^>]*aria-current="page"/', $response->getContent()),
                "Expected desktop and mobile navigation to mark {$path} as current."
            );
        }
    }

    public function test_authenticated_mobile_menu_includes_profile_notifications_and_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('reserve'))
            ->assertOk()
            ->assertSee('Profile settings')
            ->assertSee('Notifications')
            ->assertSee('Staff dashboard')
            ->assertSee('Log out');
    }

    public function test_calendar_pages_use_mobile_friendly_initial_views_and_layouts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('calendar'))
            ->assertOk()
            ->assertSee("initialView: 'timeGridWeek'", false)
            ->assertSee('calendar-mobile-reserve', false)
            ->assertDontSee('>Today</button>', false)
            ->assertSee('calendar-page-layout', false);

        $this->get(route('calendar.manage', ['embedded' => 1]))
            ->assertOk()
            ->assertSee("initialView: 'timeGridWeek'", false)
        ->assertDontSee('>Today</button>', false)
        ->assertDontSee('Reserve this day')
            ->assertDontSee('Reserve selected day')
            ->assertSee('calendar-manage-layout', false);
    }
}
