<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityMaintenanceBlock;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarFacilityFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_filter_shows_reservations_and_maintenance_for_selected_facility(): void
    {
        $user = User::factory()->create();
        $selectedFacility = $this->createFacility('Room 101');
        $otherFacility = $this->createFacility('Room 202');

        $selectedReservation = $this->createReservation($user, $selectedFacility);
        $this->createReservation($user, $otherFacility);

        $selectedMaintenance = FacilityMaintenanceBlock::create([
            'facility_id' => $selectedFacility->id,
            'maintenance_date' => '2030-05-02',
            'is_all_day' => true,
            'reason' => 'Repairs',
        ]);
        FacilityMaintenanceBlock::create([
            'facility_id' => $otherFacility->id,
            'maintenance_date' => '2030-05-02',
            'is_all_day' => true,
            'reason' => 'Cleaning',
        ]);

        $this->actingAs($user)
            ->get(route('calendar'))
            ->assertOk()
            ->assertSee('calendarFacilityFilter', false)
            ->assertSee('All facilities')
            ->assertSee('Room 101')
            ->assertSee('Room 202');

        $this->get(route('reserve'))
            ->assertOk()
            ->assertSee('facility-calendar-frame', false)
            ->assertSee(route('calendar.manage', ['embedded' => 1]), false);

        $embeddedCalendar = $this->get(route('calendar.manage', ['embedded' => 1]))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertSee('calendarFacilityFilter', false)
            ->assertSee('All facilities')
            ->assertSee('Room 101')
            ->assertSee('Room 202')
            ->assertSee("query.set('facility_id', facilityFilter.value)", false);
        $this->assertStringContainsString(
            "frame-ancestors 'self'",
            $embeddedCalendar->headers->get('Content-Security-Policy')
        );

        $this->get(route('calendar.manage'))
            ->assertRedirect(route('reserve', ['open_facility_calendar' => 1]));

        $reservePage = $this->get(route('reserve'))->assertOk();
        $this->assertStringContainsString(
            "frame-ancestors 'none'",
            $reservePage->headers->get('Content-Security-Policy')
        );

        $this->getJson('/api/calendar/events?start=2030-05-01&end=2030-05-08&facility_id='.$selectedFacility->id)
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $selectedReservation->id])
            ->assertJsonFragment(['id' => 'maintenance-'.$selectedMaintenance->id])
            ->assertJsonMissing(['title' => 'Room 202'])
            ->assertJsonMissing(['title' => 'Unavailable: Room 202']);
    }

    private function createFacility(string $name): Facility
    {
        return Facility::create([
            'name' => $name,
            'building' => 'Main Building',
            'type' => 'Classroom',
            'capacity' => 30,
            'is_active' => true,
        ]);
    }

    private function createReservation(User $user, Facility $facility): Reservation
    {
        return Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'reservation_date' => '2030-05-01',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'purpose' => 'Meeting',
            'status' => 'approved',
        ]);
    }
}
