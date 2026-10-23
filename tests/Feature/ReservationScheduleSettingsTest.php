<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationScheduleSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_update_allowed_reservation_hours(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.reservation-settings'))
            ->assertOk()
            ->assertSee('Reservation settings')
            ->assertSee('00:00')
            ->assertSee('23:59')
            ->assertSee('aria-current="page"', false);

        $this->put(route('admin.reservation-settings.update'), [
            'start_time' => '08:00',
            'end_time' => '22:00',
            'current_password' => 'password',
        ])
            ->assertRedirect(route('admin.reservation-settings'))
            ->assertSessionHas('status', 'Reservation hours updated successfully.');

        $this->assertDatabaseHas('reservation_settings', [
            'id' => 1,
            'start_time' => '08:00',
            'end_time' => '22:00',
        ]);
    }

    public function test_reservations_are_rejected_outside_the_configured_time_window(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $facility = Facility::create([
            'name' => 'Lecture Room 1',
            'building' => 'Main Building',
            'type' => 'Classroom',
            'capacity' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($admin);
        $this->put(route('admin.reservation-settings.update'), [
            'start_time' => '08:00',
            'end_time' => '22:00',
            'current_password' => 'password',
        ])->assertRedirect(route('admin.reservation-settings'));

        $this->actingAs($user);
        $this->postJson('/api/calendar/reserve', [
            'facility' => (string) $facility->id,
            'purpose' => 'Early meeting',
            'date' => '2030-05-01',
            'start_time' => '07:00',
            'end_time' => '09:00',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'Reservations must be scheduled between 8:00 AM and 10:00 PM.');

        $this->postJson('/api/calendar/reserve', [
            'facility' => (string) $facility->id,
            'purpose' => 'Evening meeting',
            'date' => '2030-05-01',
            'start_time' => '21:00',
            'end_time' => '22:00',
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_non_admin_cannot_view_reservation_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.reservation-settings'))
            ->assertForbidden();
    }

    public function test_calendar_page_displays_the_configured_reservation_hours(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $this->actingAs($admin);
        $this->put(route('admin.reservation-settings.update'), [
            'start_time' => '07:00',
            'end_time' => '19:00',
            'current_password' => 'password',
        ]);

        $this->actingAs($user);
        $this->get('/calendar')
            ->assertOk()
            ->assertSee('"07:00"', false)
            ->assertSee('"19:00"', false)
            ->assertSee("slotMinTime: reservationOpenTime + ':00'", false)
            ->assertSee("slotMaxTime: reservationCloseTime + ':00'", false);

        $this->get('/calendar/manage')
            ->assertOk()
            ->assertSee('Reservations allowed 7:00 AM–7:00 PM.')
            ->assertSee('min="07:00"', false)
            ->assertSee('max="19:00"', false);
    }
}
