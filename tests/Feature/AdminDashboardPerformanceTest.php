<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
