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
