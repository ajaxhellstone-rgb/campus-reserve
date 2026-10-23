<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminDashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_paginates_pending_reservations_and_keeps_total_count(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $facility = Facility::create([
            'name' => 'Lecture Room 1',
            'building' => 'Main Building',
            'type' => 'Classroom',
            'capacity' => 30,
            'is_active' => true,
        ]);

        for ($index = 1; $index <= 16; $index++) {
            Reservation::create([
                'user_id' => $admin->id,
                'facility_id' => $facility->id,
                'reservation_date' => now()->addDays($index)->toDateString(),
                'start_time' => '10:00',
                'end_time' => '11:00',
                'purpose' => "Pending request {$index}",
                'status' => 'pending',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('>16</p>', false)
            ->assertSee('16 pending')
            ->assertSee('Pending request 1')
            ->assertDontSee('Pending request 16')
            ->assertSee('pendingPage=2');
    }

    public function test_dashboard_management_areas_have_separate_navigation_sections(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-admin-section="overview"', false)
            ->assertSee('data-admin-section="reservations"', false)
            ->assertSee('data-admin-section="members"', false)
            ->assertSee('data-admin-section="audit"', false)
            ->assertSee('data-admin-section-link="reservations"', false)
            ->assertSee('data-admin-section-link="members"', false)
            ->assertSee('data-admin-section-link="audit"', false)
            ->assertSee('Facilities')
            ->assertSee('Property items');
    }

    public function test_overview_graph_shows_total_approved_and_rejected_reservations(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $facility = Facility::create([
            'name' => 'Lecture Room 1',
            'building' => 'Main Building',
            'type' => 'Classroom',
            'capacity' => 30,
            'is_active' => true,
        ]);

        foreach (['approved', 'approved', 'rejected'] as $index => $status) {
            Reservation::create([
                'user_id' => $admin->id,
                'facility_id' => $facility->id,
                'reservation_date' => now()->addDays($index + 1)->toDateString(),
                'start_time' => '10:00',
                'end_time' => '11:00',
                'purpose' => "Decision {$index}",
                'status' => $status,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Reservation outcomes')
            ->assertSee('data-reservation-outcome="approved" data-total="2"', false)
            ->assertSee('data-reservation-outcome="rejected" data-total="1"', false);
    }

    public function test_ajax_member_pagination_returns_only_the_member_section(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        for ($index = 1; $index <= 16; $index++) {
            User::factory()->create([
                'name' => "Member {$index}",
                'email' => Str::lower(Str::random(12)) . '@example.com',
            ]);
        }

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard', ['role' => 'user', 'page' => 2]), [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'text/html',
            ]);

        $response
            ->assertOk()
            ->assertSee('Member Management')
            ->assertSee('member-filter-form')
            ->assertSee('aria-label="Pagination Navigation"', false)
            ->assertDontSee('Admin Dashboard')
            ->assertDontSee('Pending Reservations')
            ->assertSee('page=1');

        $this->assertSame('text/html; charset=utf-8', strtolower($response->headers->get('Content-Type')));
    }

    public function test_ajax_member_search_returns_the_matching_email_without_full_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create([
            'name' => 'Search Target',
            'email' => 'target.member@example.com',
        ]);
        User::factory()->create([
            'name' => 'Other Member',
            'email' => 'other.member@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['search' => 'target.member@example.com']), [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'text/html',
            ])
            ->assertOk()
            ->assertSee('target.member@example.com')
            ->assertDontSee('other.member@example.com')
            ->assertDontSee('Admin Dashboard')
            ->assertDontSee('Pending Reservations');
    }
}
