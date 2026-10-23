<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityItem;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationPendingApproval;
use App\Notifications\ReservationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ChatbotPropertyReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2030-04-01'));
    }

    public function test_chatbot_submits_property_reservations_to_the_admin_approval_queue(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);
        $item = FacilityItem::create([
            'facility_id' => null,
            'name' => 'Projector',
            'quantity' => 3,
        ]);

        $prompt = $this->actingAs($user)
            ->postJson(route('chatbot.respond'), [
                'message' => 'Reserve 2 projectors on May 1 2030 from 10 AM to 12 PM',
            ]);
        $prompt->assertOk();
        $this->assertStringContainsString('What is the purpose', $prompt->json('reply'));

        $prompt = $this->postJson(route('chatbot.respond'), ['message' => 'Classroom presentation']);
        $prompt->assertOk();
        $this->assertStringContainsString('Should I submit this property reservation request', $prompt->json('reply'));
        $this->assertStringContainsString('use time is 10:00 AM', $prompt->json('reply'));
        $this->assertStringContainsString('return time is 12:00 PM', $prompt->json('reply'));

        $submitted = $this->postJson(route('chatbot.respond'), ['message' => 'yes']);
        $submitted->assertOk();
        $this->assertStringContainsString('submitted as a pending request', $submitted->json('reply'));

        $reservation = Reservation::firstOrFail();
        $this->assertSame('pending', $reservation->status);
        $this->assertNull($reservation->facility_id);
        $this->assertSame('Classroom presentation', $reservation->purpose);
        $this->assertDatabaseHas('reservation_items', [
            'reservation_id' => $reservation->id,
            'facility_item_id' => $item->id,
            'quantity' => 2,
        ]);

        Notification::assertSentTo($admin, ReservationPendingApproval::class, function (ReservationPendingApproval $notification) use ($admin) {
            $message = $notification->toArray($admin)['message'];

            return str_contains($message, 'Use time: 10:00')
                && str_contains($message, 'return time: 12:00')
                && str_contains($message, 'Purpose: Classroom presentation')
                && str_contains($message, 'Projector × 2');
        });
        Notification::assertSentTo($user, ReservationStatusChanged::class, function ($notification, $channels) {
            return $channels === ['database'];
        });
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Items:')
            ->assertSee('Projector')
            ->assertSee('× 2')
            ->assertSee('Classroom presentation');

        $this->actingAs($user);
        $status = $this->postJson(route('chatbot.respond'), [
            'message' => 'What is the status of my property reservation?',
        ]);
        $status->assertOk();
        $this->assertStringContainsString('Projector × 2', $status->json('reply'));
        $this->assertStringContainsString('pending', $status->json('reply'));
        $this->assertStringContainsString('use at 10:00 AM, return at 12:00 PM', $status->json('reply'));
        $this->assertStringContainsString('purpose: Classroom presentation', $status->json('reply'));
    }

    public function test_chatbot_rejects_reservation_dates_outside_the_booking_window(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('chatbot.respond'), [
                'message' => 'Reserve a projector on May 1 2041 from 10 AM to 12 PM',
            ])
            ->assertOk()
            ->assertJsonPath('reply', 'Reservations can be made up to 2032-04-01. Please choose an earlier date.');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_chatbot_asks_for_use_and_return_times_when_property_booking_times_are_missing(): void
    {
        $user = User::factory()->create();
        FacilityItem::create([
            'facility_id' => null,
            'name' => 'Projector',
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('chatbot.respond'), [
                'message' => 'I want to reserve a projector tomorrow',
            ]);

        $response->assertOk();
        $this->assertStringContainsString('What is the purpose', $response->json('reply'));

        $response = $this->postJson(route('chatbot.respond'), ['message' => 'Borrowing for a lecture']);
        $response->assertOk();
        $this->assertStringContainsString('What time will you use the items', $response->json('reply'));
        $this->assertStringContainsString('what time will you return them', strtolower($response->json('reply')));
    }

    public function test_chatbot_understands_weekday_dates_and_shared_meridiem_times_with_one_availability_query(): void
    {
        $user = User::factory()->create();
        $facility = $this->createFacility();
        Facility::create([
            'name' => 'Study Room 2',
            'building' => 'Main Building',
            'type' => 'Classroom',
            'capacity' => 20,
            'is_active' => true,
        ]);
        Facility::create([
            'name' => 'Conference Room 3',
            'building' => 'North Building',
            'type' => 'Conference',
            'capacity' => 12,
            'is_active' => true,
        ]);
        $this->travelTo(now()->setDate(2030, 5, 1)->setTime(8, 0));

        $availabilityQueries = 0;
        DB::listen(function ($query) use (&$availabilityQueries): void {
            if (str_contains($query->sql, 'from "reservations"')
                && str_contains($query->sql, '"facility_id" in')) {
                $availabilityQueries++;
            }
        });

        $response = $this->actingAs($user)
            ->postJson(route('chatbot.respond'), [
                'message' => 'Check room availability next Friday from 9 to 11 PM',
            ]);

        $response->assertOk();
        $this->assertStringContainsString('May 3, 2030', $response->json('reply'));
        $this->assertStringContainsString('9:00 PM to 11:00 PM', $response->json('reply'));
        $this->assertStringContainsString($facility->name, $response->json('reply'));
        $this->assertSame(1, $availabilityQueries);
    }

    public function test_chatbot_answers_questions_about_the_next_upcoming_reservation(): void
    {
        $user = User::factory()->create();
        $facility = $this->createFacility();
        $this->travelTo(now()->setDate(2030, 5, 1)->setTime(8, 0));

        Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'reservation_date' => '2030-04-30',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'purpose' => 'Past presentation',
            'status' => 'approved',
        ]);
        Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'reservation_date' => '2030-05-03',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'purpose' => 'Upcoming presentation',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('chatbot.respond'), ['message' => 'When is my next booking?']);

        $response->assertOk();
        $this->assertStringContainsString('Your next reservation is Lecture Room 1', $response->json('reply'));
        $this->assertStringContainsString('May 3, 2030', $response->json('reply'));
        $this->assertStringContainsString('accepted', $response->json('reply'));
        $this->assertStringNotContainsString('April 30, 2030', $response->json('reply'));
    }

    public function test_chatbot_collects_purpose_before_submitting_a_facility_reservation(): void
    {
        $user = User::factory()->create();
        $facility = $this->createFacility();

        $response = $this->actingAs($user)
            ->postJson(route('chatbot.respond'), [
                'message' => 'Reserve Lecture Room 1 on May 1 2030 from 10 AM to 12 PM',
            ]);

        $response->assertOk();
        $this->assertStringContainsString('What is the purpose', $response->json('reply'));
        $this->assertDatabaseCount('reservations', 0);

        $response = $this->postJson(route('chatbot.respond'), ['message' => 'Student club meeting']);
        $response->assertOk();
        $this->assertStringContainsString('Should I submit this reservation request', $response->json('reply'));

        $response = $this->postJson(route('chatbot.respond'), ['message' => 'yes']);
        $response->assertOk();
        $this->assertDatabaseHas('reservations', [
            'facility_id' => $facility->id,
            'purpose' => 'Student club meeting',
            'status' => 'pending',
        ]);
    }

    public function test_chatbot_confirmation_without_a_purpose_collects_it_before_submission(): void
    {
        $user = User::factory()->create();
        $facility = $this->createFacility();
        $this->actingAs($user);

        $response = $this->withSession([
            'chatbot_reservation' => [
                'facility_id' => $facility->id,
                'date' => '2030-05-01',
                'start_time' => '10:00',
                'end_time' => '12:00',
                'reservation_type' => 'facility',
                'items' => [],
                'reservation_intent' => true,
                'awaiting_confirmation' => true,
            ],
        ])->postJson(route('chatbot.respond'), ['message' => 'yes']);

        $response->assertOk();
        $this->assertStringContainsString('What is the purpose', $response->json('reply'));
        $this->assertDatabaseCount('reservations', 0);

        $response = $this->postJson(route('chatbot.respond'), ['message' => 'Student workshop']);
        $response->assertOk();
        $this->assertStringContainsString('submitted as a pending request', $response->json('reply'));
        $this->assertDatabaseHas('reservations', [
            'facility_id' => $facility->id,
            'purpose' => 'Student workshop',
            'status' => 'pending',
        ]);
    }

    public function test_chatbot_reports_when_requested_item_quantity_is_unavailable(): void
    {
        $user = User::factory()->create();
        FacilityItem::create([
            'facility_id' => null,
            'name' => 'Projector',
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('chatbot.respond'), [
                'message' => 'Reserve 2 projectors on May 1 2030 from 10 AM to 12 PM',
            ]);
        $response->assertOk();
        $this->assertStringContainsString('What is the purpose', $response->json('reply'));

        $response = $this->postJson(route('chatbot.respond'), ['message' => 'Class presentation']);
        $response->assertOk();
        $this->assertStringContainsString('only 1 available', $response->json('reply'));

        $this->assertDatabaseCount('reservations', 0);
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
}
