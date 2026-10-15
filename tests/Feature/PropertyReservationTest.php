<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityItem;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_item_availability_counts_only_overlapping_approved_reservations(): void
    {
        $user = User::factory()->create();
        $facility = $this->createFacility();
        $item = FacilityItem::create([
            'facility_id' => $facility->id,
            'name' => 'Projector',
            'quantity' => 4,
        ]);

        $approved = $this->createReservation($user, $facility, 'approved', '10:00', '12:00');
        $approved->items()->attach($item, ['quantity' => 2]);
        $pending = $this->createReservation($user, $facility, 'pending', '09:00', '10:45');
        $pending->items()->attach($item, ['quantity' => 1]);
        $nonOverlapping = $this->createReservation($user, $facility, 'approved', '12:00', '13:00');
        $nonOverlapping->items()->attach($item, ['quantity' => 1]);

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
            ->assertJsonPath('items.0.available', 2);
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
