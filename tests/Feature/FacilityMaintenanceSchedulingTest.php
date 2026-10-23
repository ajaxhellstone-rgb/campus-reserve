<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityMaintenanceBlock;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FacilityMaintenanceSchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2030-04-01'));
    }

    public function test_admin_can_schedule_maintenance_and_overlapping_reservations_are_rejected_and_notified(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $facility = $this->createFacility();
        $pending = $this->createReservation($user, $facility, 'pending', '09:00', '10:30');
        $approved = $this->createReservation($user, $facility, 'approved', '10:30', '12:00');
        $unaffected = $this->createReservation($user, $facility, 'approved', '13:00', '14:00');

        $this->actingAs($admin)
            ->post(route('admin.reservation-settings.maintenance.store'), [
                'facility_id' => $facility->id,
                'maintenance_date' => '2030-05-01',
                'is_all_day' => '0',
                'start_time' => '10:00',
                'end_time' => '11:00',
                'reason' => 'Electrical maintenance',
                'current_password' => 'password',
            ])
            ->assertRedirect(route('admin.reservation-settings'))
            ->assertSessionHas('status', 'Maintenance block scheduled. Overlapping reservations were automatically rejected and their owners notified.');

        $this->assertDatabaseHas('facility_maintenance_blocks', [
            'facility_id' => $facility->id,
            'maintenance_date' => '2030-05-01 00:00:00',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'is_all_day' => 0,
            'reason' => 'Electrical maintenance',
        ]);
        $this->assertSame('rejected', $pending->fresh()->status);
        $this->assertSame('rejected', $approved->fresh()->status);
        $this->assertSame('approved', $unaffected->fresh()->status);
        $this->assertNotNull($approved->fresh()->rejected_at);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => ReservationStatusChanged::class,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'reservation_id' => $pending->id,
            'action' => 'rejected',
        ]);
    }

    public function test_all_day_maintenance_rejects_reservations_and_is_visible_on_shared_calendar(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $facility = $this->createFacility();
        $reservation = $this->createReservation($user, $facility, 'pending', '18:00', '20:00');

        $this->actingAs($admin)
            ->post(route('admin.reservation-settings.maintenance.store'), [
                'facility_id' => $facility->id,
                'maintenance_date' => '2030-05-01',
                'is_all_day' => '1',
                'reason' => 'Room repairs',
                'current_password' => 'password',
            ])
            ->assertRedirect(route('admin.reservation-settings'));

        $this->assertSame('rejected', $reservation->fresh()->status);

        $this->getJson('/api/calendar/events?start=2030-05-01&end=2030-05-02')
            ->assertOk()
            ->assertJsonFragment([
                'id' => 'maintenance-1',
                'title' => 'Unavailable: Lecture Room 1',
                'reason' => 'Room repairs',
                'is_maintenance' => true,
            ]);
    }

    public function test_maintenance_blocks_new_reservations_and_item_availability_but_allows_non_overlapping_time(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $facility = $this->createFacility();

        $this->actingAs($admin)
            ->post(route('admin.reservation-settings.maintenance.store'), [
                'facility_id' => $facility->id,
                'maintenance_date' => '2030-05-01',
                'is_all_day' => '0',
                'start_time' => '10:00',
                'end_time' => '12:00',
                'reason' => 'Equipment inspection',
                'current_password' => 'password',
            ])
            ->assertRedirect(route('admin.reservation-settings'));

        $this->actingAs($user)
            ->postJson('/api/calendar/reserve', [
                'facility' => (string) $facility->id,
                'purpose' => 'Meeting',
                'date' => '2030-05-01',
                'start_time' => '11:00',
                'end_time' => '13:00',
            ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'facility_maintenance')
            ->assertJsonPath('error', 'This facility is unavailable: Equipment inspection.');

        $this->getJson("/api/facilities/{$facility->id}/item-availability?date=2030-05-01&start_time=11:00&end_time=13:00")
            ->assertStatus(409)
            ->assertJsonPath('message', 'This facility is unavailable: Equipment inspection.');

        $this->postJson('/api/calendar/reserve', [
            'facility' => (string) $facility->id,
            'purpose' => 'Meeting',
            'date' => '2030-05-01',
            'start_time' => '12:00',
            'end_time' => '13:00',
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_admin_cannot_approve_reservation_during_maintenance(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $facility = $this->createFacility();
        $reservation = $this->createReservation($user, $facility, 'pending', '10:00', '11:00');
        FacilityMaintenanceBlock::create([
            'facility_id' => $facility->id,
            'maintenance_date' => '2030-05-01',
            'start_time' => '10:30',
            'end_time' => '12:00',
            'is_all_day' => false,
            'reason' => 'Emergency repair',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.reservations.approve', $reservation), [
                'current_password' => 'password',
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'This facility is unavailable: Emergency repair.');

        $this->assertSame('pending', $reservation->fresh()->status);
    }

    public function test_maintenance_requires_a_reason_and_a_valid_time_range_when_not_all_day(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $facility = $this->createFacility();

        $this->actingAs($admin)
            ->from(route('admin.reservation-settings'))
            ->post(route('admin.reservation-settings.maintenance.store'), [
                'facility_id' => $facility->id,
                'maintenance_date' => '2030-05-01',
                'is_all_day' => '0',
                'start_time' => '12:00',
                'end_time' => '11:00',
                'reason' => '',
                'current_password' => 'password',
            ])
            ->assertRedirect(route('admin.reservation-settings'))
            ->assertSessionHasErrors(['end_time', 'reason']);
    }

    public function test_admin_can_remove_a_maintenance_block_with_password_confirmation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $facility = $this->createFacility();
        $block = FacilityMaintenanceBlock::create([
            'facility_id' => $facility->id,
            'maintenance_date' => '2030-05-01',
            'start_time' => null,
            'end_time' => null,
            'is_all_day' => true,
            'reason' => 'Room repairs',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reservation-settings'))
            ->assertOk()
            ->assertSee('Facility maintenance')
            ->assertSee('Room repairs')
            ->assertSee('password_confirmation_dialog', false)
            ->assertSee('data-password-confirm', false);

        $this->delete(route('admin.reservation-settings.maintenance.destroy', $block), [
            'current_password' => 'password',
        ])
            ->assertRedirect(route('admin.reservation-settings'))
            ->assertSessionHas('status', 'Maintenance block removed.');

        $this->assertDatabaseMissing('facility_maintenance_blocks', ['id' => $block->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'facility_maintenance_removed']);
    }

    public function test_reservation_settings_actions_require_the_correct_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $facility = $this->createFacility();
        $block = FacilityMaintenanceBlock::create([
            'facility_id' => $facility->id,
            'maintenance_date' => '2030-05-01',
            'is_all_day' => true,
            'reason' => 'Room repairs',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.reservation-settings.update'), [
                'start_time' => '08:00',
                'end_time' => '22:00',
                'current_password' => 'incorrect',
            ])
            ->assertSessionHasErrors('current_password');
        $this->assertDatabaseMissing('reservation_settings', ['id' => 1]);

        $this->post(route('admin.reservation-settings.maintenance.store'), [
            'facility_id' => $facility->id,
            'maintenance_date' => '2030-05-02',
            'is_all_day' => '1',
            'reason' => 'Electrical maintenance',
            'current_password' => 'incorrect',
        ])->assertSessionHasErrors('current_password');
        $this->assertDatabaseMissing('facility_maintenance_blocks', [
            'facility_id' => $facility->id,
            'maintenance_date' => '2030-05-02 00:00:00',
        ]);

        $this->delete(route('admin.reservation-settings.maintenance.destroy', $block), [
            'current_password' => 'incorrect',
        ])->assertSessionHasErrors('current_password');
        $this->assertDatabaseHas('facility_maintenance_blocks', ['id' => $block->id]);
    }

    private function createFacility(): Facility
    {
        return Facility::create([
            'name' => 'Lecture Room 1',
            'building' => 'Main Building',
            'type' => 'Classroom',
            'capacity' => 30,
            'is_active' => true,
        ]);
    }

    private function createReservation(User $user, Facility $facility, string $status, string $start, string $end): Reservation
    {
        return Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'reservation_date' => '2030-05-01',
            'start_time' => $start,
            'end_time' => $end,
            'purpose' => 'Meeting',
            'status' => $status,
        ]);
    }
}
