<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityItem;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PropertyReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserve_page_shows_facility_and_property_tabs_and_item_stock(): void
    {
        $user = User::factory()->create();
        $facility = $this->createFacility();
        FacilityItem::create([
            'facility_id' => $facility->id,
            'name' => 'Projector',
            'quantity' => 3,
        ]);

        $this->actingAs($user)
            ->get(route('reserve'))
            ->assertOk()
            ->assertSee('Reserve Facility')
            ->assertSee('Reserve Property')
            ->assertSee('Projector')
            ->assertSee('3 in stock')
            ->assertSee('Reserve Items');
    }

    public function test_reserve_page_shows_property_item_quantity_statistics(): void
    {
        $user = User::factory()->create();
        $facility = $this->createFacility();
        $item = FacilityItem::create([
            'facility_id' => $facility->id,
            'name' => 'Projector',
            'quantity' => 5,
        ]);

        $borrowed = $this->createReservation($user, $facility, 'approved', '10:00', '12:00');
        $borrowed->items()->attach($item, ['quantity' => 2]);
        $returned = $this->createReservation($user, $facility, 'completed', '12:00', '13:00');
        $returned->items()->attach($item, ['quantity' => 3]);
        $pending = $this->createReservation($user, $facility, 'pending', '13:00', '14:00');
        $pending->items()->attach($item, ['quantity' => 1]);

        $this->actingAs($user)
            ->get(route('reserve'))
            ->assertOk()
            ->assertSee('Borrowed Items')
            ->assertSee('Returned Items')
            ->assertSeeInOrder(['Borrowed Items', '2</p>', 'Returned Items', '3</p>']);
    }

    public function test_calendar_shows_all_approved_reservations_but_only_exposes_owner_purpose(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $facility = $this->createFacility();
        $ownReservation = $this->createReservation($user, $facility, 'approved', '10:00', '11:00');
        $otherReservation = $this->createReservation($otherUser, $facility, 'approved', '11:00', '12:00');
        $hiddenPendingReservation = $this->createReservation($otherUser, $facility, 'pending', '12:00', '13:00');
        $outsideRangeReservation = $this->createReservation($otherUser, $facility, 'approved', '13:00', '14:00');

        $ownReservation->update(['purpose' => 'My meeting']);
        $otherReservation->update(['purpose' => 'Private meeting']);
        $outsideRangeReservation->update(['reservation_date' => '2030-05-02']);

        $this->actingAs($user)
            ->getJson('/api/calendar/events?start=2030-05-01&end=2030-05-02')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment([
                'id' => $ownReservation->id,
                'purpose' => 'My meeting',
                'is_owner' => true,
            ])
            ->assertJsonFragment([
                'id' => $otherReservation->id,
                'purpose' => null,
                'is_owner' => false,
            ])
            ->assertJsonMissing(['id' => $hiddenPendingReservation->id])
            ->assertJsonMissing(['id' => $outsideRangeReservation->id]);
    }

    public function test_calendar_events_require_a_date_range(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/calendar/events')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start', 'end']);
    }

    public function test_reserving_an_occupied_room_returns_the_friendly_conflict_response(): void
    {
        $user = User::factory()->create();
        $facility = $this->createFacility();
        $this->createReservation($user, $facility, 'approved', '10:00', '12:00');
        $this->assertFalse($facility->fresh()->isAvailable('2030-05-01', '11:00', '13:00'));

        $this->actingAs($user)
            ->postJson('/api/calendar/reserve', [
                'facility' => (string) $facility->id,
                'purpose' => 'Club meeting',
                'date' => '2030-05-01',
                'start_time' => '11:00',
                'end_time' => '13:00',
            ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'schedule_occupied')
            ->assertJsonPath('error', "This schedule and room is already occupied!\nPlease try another time or room!");

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_same_start_time_database_conflicts_return_the_friendly_response(): void
    {
        $user = User::factory()->create();
        $facility = $this->createFacility();
        $this->createReservation($user, $facility, 'pending', '10:00', '12:00');

        $this->actingAs($user)
            ->postJson('/api/calendar/reserve', [
                'facility' => (string) $facility->id,
                'purpose' => 'Club meeting',
                'date' => '2030-05-01',
                'start_time' => '10:00',
                'end_time' => '11:00',
            ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'schedule_occupied')
            ->assertJsonPath('error', "This schedule and room is already occupied!\nPlease try another time or room!");

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_user_can_reserve_facility_items_with_date_and_time(): void
    {
        $user = User::factory()->create();
        $facility = $this->createFacility();
        $item = FacilityItem::create([
            'facility_id' => $facility->id,
            'name' => 'Projector',
            'quantity' => 3,
        ]);

        $this->actingAs($user)
            ->postJson('/api/calendar/reserve', [
                'facility' => (string) $facility->id,
                'purpose' => 'Club meeting',
                'date' => '2030-05-01',
                'start_time' => '10:00',
                'end_time' => '12:00',
                'items' => [
                    ['facility_item_id' => $item->id, 'quantity' => 2],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $reservation = Reservation::firstOrFail();
        $this->assertSame('pending', $reservation->status);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => ReservationStatusChanged::class,
        ]);
        $this->assertDatabaseHas('reservation_items', [
            'reservation_id' => $reservation->id,
            'facility_item_id' => $item->id,
            'quantity' => 2,
        ]);
    }

    public function test_user_cannot_reserve_items_assigned_to_another_facility(): void
    {
        $user = User::factory()->create();
        $facility = $this->createFacility();
        $otherFacility = Facility::create([
            'name' => 'Conference Room',
            'building' => 'Main Building',
            'type' => 'Conference Room',
            'capacity' => 20,
            'is_active' => true,
        ]);
        $item = FacilityItem::create([
            'facility_id' => $otherFacility->id,
            'name' => 'Projector',
            'quantity' => 2,
        ]);

        $this->actingAs($user)
            ->postJson('/api/calendar/reserve', [
                'facility' => (string) $facility->id,
                'purpose' => 'Club meeting',
                'date' => '2030-05-01',
                'start_time' => '10:00',
                'end_time' => '12:00',
                'items' => [
                    ['facility_item_id' => $item->id, 'quantity' => 1],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_item_availability_counts_only_overlapping_approved_reservations(): void
    {
        $user = User::factory()->create();
        $facility = $this->createFacility();
        $item = FacilityItem::create([
            'facility_id' => $facility->id,
            'name' => 'Projector',
            'quantity' => 4,
        ]);
        $secondItem = FacilityItem::create([
            'facility_id' => $facility->id,
            'name' => 'Whiteboard',
            'quantity' => 5,
        ]);

        $approved = $this->createReservation($user, $facility, 'approved', '10:00', '12:00');
        $approved->items()->attach($item, ['quantity' => 2]);
        $approved->items()->attach($secondItem, ['quantity' => 3]);
        $pending = $this->createReservation($user, $facility, 'pending', '09:00', '10:45');
        $pending->items()->attach($item, ['quantity' => 1]);
        $nonOverlapping = $this->createReservation($user, $facility, 'approved', '12:00', '13:00');
        $nonOverlapping->items()->attach($item, ['quantity' => 1]);
        $availabilityQueries = 0;
        DB::listen(function ($query) use (&$availabilityQueries): void {
            if (str_contains($query->sql, 'SUM(reservation_items.quantity)')) {
                $availabilityQueries++;
            }
        });

        $this->actingAs($user)
            ->getJson(route('api.facilities.items.availability', [
                'facility' => $facility,
                'date' => '2030-05-01',
                'start_time' => '10:30',
                'end_time' => '11:30',
            ]))
            ->assertOk()
            ->assertJsonPath('items.0.id', $item->id)
            ->assertJsonPath('items.0.stock', 4)
            ->assertJsonPath('items.0.available', 2)
            ->assertJsonPath('items.1.id', $secondItem->id)
            ->assertJsonPath('items.1.available', 2);

        $this->assertSame(1, $availabilityQueries);
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
        string $status,
        string $startTime,
        string $endTime
    ): Reservation {
        return Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'reservation_date' => '2030-05-01',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'purpose' => 'Test booking',
            'status' => $status,
        ]);
    }
}
