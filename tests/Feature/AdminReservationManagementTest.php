<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityItem;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminReservationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_an_edit_reservations_section_for_approved_bookings(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();
        $facility = $this->createFacility();
        $reservation = $this->createReservation($user, $facility);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Edit Reservations')
            ->assertSee('Reservation #'.$reservation->id)
            ->assertSee('name="reservation_date"', false)
            ->assertSee('Delete reservation')
            ->assertSee('Save changes');
    }

    public function test_admin_can_edit_an_approved_reservation_directly(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();
        $facility = $this->createFacility();
        $reservation = $this->createReservation($user, $facility);

        $this->actingAs($admin)
            ->put(route('admin.reservations.update', $reservation), [
                'current_password' => 'password',
                'reservation_date' => now()->addDays(2)->toDateString(),
                'start_time' => '13:00',
                'end_time' => '14:30',
                'purpose' => 'Updated presentation',
            ])
            ->assertRedirect(route('admin.dashboard').'#reservations')
            ->assertSessionHas('status', 'Approved reservation updated successfully.');

        $this->assertSame(now()->addDays(2)->toDateString(), $reservation->fresh()->reservation_date->format('Y-m-d'));
        $this->assertSame('13:00', $reservation->fresh()->start_time->format('H:i'));
        $this->assertSame('14:30', $reservation->fresh()->end_time->format('H:i'));
        $this->assertSame('Updated presentation', $reservation->fresh()->purpose);
        $this->assertSame('approved', $reservation->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'reservation_id' => $reservation->id,
            'action' => 'reservation_updated',
        ]);
    }

    public function test_admin_cannot_edit_an_approved_reservation_into_an_occupied_schedule(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();
        $facility = $this->createFacility();
        $reservation = $this->createReservation($user, $facility);
        $conflictingReservation = $this->createReservation($user, $facility, '14:00', '15:00');

        $this->actingAs($admin)
            ->putJson(route('admin.reservations.update', $reservation), [
                'current_password' => 'password',
                'reservation_date' => $conflictingReservation->reservation_date->format('Y-m-d'),
                'start_time' => '14:30',
                'end_time' => '15:30',
                'purpose' => 'Updated presentation',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_time');

        $this->assertSame('10:00', $reservation->fresh()->start_time->format('H:i'));
    }

    public function test_admin_cannot_reschedule_approved_property_items_beyond_available_stock(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $facility = $this->createFacility();
        $item = FacilityItem::create([
            'facility_id' => null,
            'name' => 'Projector',
            'quantity' => 2,
        ]);
        $reservation = $this->createReservation($user, $facility);
        $reservation->update(['facility_id' => null]);
        $reservation->items()->attach($item, ['quantity' => 2]);

        $otherReservation = $this->createReservation($otherUser, $facility, '13:00', '14:00');
        $otherReservation->update([
            'facility_id' => null,
            'reservation_date' => now()->addDays(2)->toDateString(),
        ]);
        $otherReservation->items()->attach($item, ['quantity' => 1]);

        $this->actingAs($admin)
            ->putJson(route('admin.reservations.update', $reservation), [
                'current_password' => 'password',
                'reservation_date' => now()->addDays(2)->toDateString(),
                'start_time' => '13:00',
                'end_time' => '14:00',
                'purpose' => 'Updated presentation',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reservation_date');

        $this->assertSame(
            $reservation->reservation_date->format('Y-m-d'),
            $reservation->fresh()->reservation_date->format('Y-m-d')
        );
    }

    public function test_admin_deleting_an_approved_reservation_removes_it_and_notifies_the_owner(): void
    {
        Notification::fake();

        $admin = $this->createAdmin();
        $user = User::factory()->create();
        $facility = $this->createFacility();
        $reservation = $this->createReservation($user, $facility);

        $this->actingAs($admin)
            ->deleteJson(route('admin.reservations.destroy', $reservation), [
                'current_password' => 'password',
            ])
            ->assertJson([
                'status' => 'Reservation deleted successfully and the borrower was notified.',
            ]);

        $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'details' => 'Reservation cancelled and deleted by admin.',
        ]);
        Notification::assertSentTo($user, ReservationStatusChanged::class, function (ReservationStatusChanged $notification) use ($reservation) {
            return $notification->toArray($reservation->user)['status'] === 'cancelled'
                && str_contains($notification->toArray($reservation->user)['message'], 'cancelled');
        });
    }

    public function test_admin_edit_actions_require_current_password(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();
        $reservation = $this->createReservation($user, $this->createFacility());

        $this->actingAs($admin)
            ->putJson(route('admin.reservations.update', $reservation), [
                'reservation_date' => now()->addDays(2)->toDateString(),
                'start_time' => '13:00',
                'end_time' => '14:00',
                'purpose' => 'Updated presentation',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->deleteJson(route('admin.reservations.destroy', $reservation))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertSame('approved', $reservation->fresh()->status);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);
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

    private function createReservation(
        User $user,
        Facility $facility,
        string $startTime = '10:00',
        string $endTime = '11:00'
    ): Reservation {
        return Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'reservation_date' => now()->addDay()->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'purpose' => 'Presentation',
            'status' => 'approved',
        ]);
    }
}
