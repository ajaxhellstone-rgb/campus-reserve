<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityItem;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CompleteExpiredReservationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_completes_all_ended_approved_reservations_and_notifies_the_borrower(): void
    {
        Notification::fake();
        $this->travelTo(now()->setTime(15, 0));

        $user = User::factory()->create();
        $facility = Facility::create([
            'name' => 'Lecture Room 1',
            'building' => 'Main Building',
            'type' => 'Classroom',
            'capacity' => 30,
            'is_active' => true,
        ]);
        $pastReservation = $this->createReservation($user, $facility, now()->subDay()->toDateString(), '10:00', '11:00');
        $endedTodayReservation = $this->createReservation($user, $facility, now()->toDateString(), '12:00', '14:00');
        $ongoingReservation = $this->createReservation($user, $facility, now()->toDateString(), '14:00', '16:00');
        $futureReservation = $this->createReservation($user, $facility, now()->addDay()->toDateString(), '10:00', '11:00');
        $propertyReservation = $this->createReservation($user, $facility, now()->subDay()->toDateString(), '12:00', '13:00');
        $item = FacilityItem::create([
            'facility_id' => $facility->id,
            'name' => 'Projector',
            'quantity' => 2,
        ]);
        $propertyReservation->items()->attach($item, ['quantity' => 1]);

        Artisan::call('reservations:complete-expired');

        $this->assertDatabaseHas('reservations', ['id' => $pastReservation->id, 'status' => 'completed']);
        $this->assertDatabaseHas('reservations', ['id' => $endedTodayReservation->id, 'status' => 'completed']);
        $this->assertDatabaseHas('reservations', ['id' => $ongoingReservation->id, 'status' => 'approved']);
        $this->assertDatabaseHas('reservations', ['id' => $futureReservation->id, 'status' => 'approved']);
        $this->assertDatabaseHas('reservations', ['id' => $propertyReservation->id, 'status' => 'completed']);
        Notification::assertSentToTimes($user, ReservationStatusChanged::class, 3);

        Artisan::call('reservations:complete-expired');

        Notification::assertSentToTimes($user, ReservationStatusChanged::class, 3);
    }

    private function createReservation(
        User $user,
        Facility $facility,
        string $date,
        string $startTime,
        string $endTime
    ): Reservation {
        return Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'reservation_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'purpose' => 'Test booking',
            'status' => 'approved',
        ]);
    }
}
