<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RejectExpiredReservationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_past_pending_reservations_and_notifies_the_borrower(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $facility = Facility::create([
            'name' => 'Lecture Room 1',
            'building' => 'Main Building',
            'type' => 'Classroom',
            'capacity' => 30,
            'is_active' => true,
        ]);
        $expiredPending = $this->createReservation($user, $facility, 'pending', now()->subDay()->toDateString(), '10:00');
        $todayPending = $this->createReservation($user, $facility, 'pending', now()->toDateString(), '10:00');
        $upcomingPending = $this->createReservation($user, $facility, 'pending', now()->addDay()->toDateString(), '10:00');
        $expiredApproved = $this->createReservation($user, $facility, 'approved', now()->subDay()->toDateString(), '11:00');

        Artisan::call('reservations:reject-expired');

        $this->assertDatabaseHas('reservations', [
            'id' => $expiredPending->id,
            'status' => 'rejected',
            'admin_remarks' => 'Automatically rejected because the reservation date has passed.',
        ]);
        $this->assertNotNull($expiredPending->fresh()->rejected_at);
        $this->assertDatabaseHas('reservations', ['id' => $todayPending->id, 'status' => 'pending']);
        $this->assertDatabaseHas('reservations', ['id' => $upcomingPending->id, 'status' => 'pending']);
        $this->assertDatabaseHas('reservations', ['id' => $expiredApproved->id, 'status' => 'approved']);
        Notification::assertSentTo($user, ReservationStatusChanged::class, function (ReservationStatusChanged $notification) use ($expiredPending): bool {
            return $notification->reservation->is($expiredPending)
                && $notification->reservation->status === 'rejected';
        });

        Artisan::call('reservations:reject-expired');

        Notification::assertSentToTimes($user, ReservationStatusChanged::class, 1);
    }

    public function test_it_deletes_rejected_reservations_after_three_full_days(): void
    {
        $user = User::factory()->create();
        $facility = Facility::create([
            'name' => 'Lecture Room 1',
            'building' => 'Main Building',
            'type' => 'Classroom',
            'capacity' => 30,
            'is_active' => true,
        ]);

        $expiredRejected = $this->createReservation($user, $facility, 'rejected', now()->toDateString(), '10:00');
        $expiredRejected->forceFill(['rejected_at' => now()->subDays(3)])->save();
        $recentlyRejected = $this->createReservation($user, $facility, 'rejected', now()->toDateString(), '11:00');
        $recentlyRejected->forceFill(['rejected_at' => now()->subDays(2)])->save();
        $oldPending = $this->createReservation($user, $facility, 'pending', now()->toDateString(), '12:00');
        $oldPending->forceFill(['rejected_at' => now()->subDays(5)])->save();

        Artisan::call('reservations:delete-expired-rejected');

        $this->assertDatabaseMissing('reservations', ['id' => $expiredRejected->id]);
        $this->assertDatabaseHas('reservations', ['id' => $recentlyRejected->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('reservations', ['id' => $oldPending->id, 'status' => 'pending']);
    }

    public function test_admin_rejection_records_the_rejection_time(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);
        $user = User::factory()->create();
        $facility = Facility::create([
            'name' => 'Lecture Room 1',
            'building' => 'Main Building',
            'type' => 'Classroom',
            'capacity' => 30,
            'is_active' => true,
        ]);
        $reservation = $this->createReservation($user, $facility, 'pending', now()->addDay()->toDateString(), '10:00');

        $this->actingAs($admin)
            ->post(route('admin.reservations.reject', $reservation), ['current_password' => 'password'])
            ->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'rejected',
        ]);
        $this->assertNotNull($reservation->fresh()->rejected_at);
    }

    public function test_admin_can_reject_a_reservation_without_a_redirect(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);
        $user = User::factory()->create();
        $facility = Facility::create([
            'name' => 'Lecture Room 2',
            'building' => 'Main Building',
            'type' => 'Classroom',
            'capacity' => 30,
            'is_active' => true,
        ]);
        $reservation = $this->createReservation($user, $facility, 'pending', now()->addDay()->toDateString(), '12:00');

        $this->actingAs($admin)
            ->postJson(route('admin.reservations.reject', $reservation), ['current_password' => 'password'])
            ->assertOk()
            ->assertJsonPath('status', 'Reservation rejected successfully.')
            ->assertJsonPath('pending_count', 0)
            ->assertJsonPath('audit_log.action', 'rejected');

        $this->assertSame('rejected', $reservation->fresh()->status);
    }

    private function createReservation(
        User $user,
        Facility $facility,
        string $status,
        string $date,
        string $startTime
    ): Reservation {
        return Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'reservation_date' => $date,
            'start_time' => $startTime,
            'end_time' => '11:00',
            'purpose' => 'Test booking',
            'status' => $status,
        ]);
    }
}
