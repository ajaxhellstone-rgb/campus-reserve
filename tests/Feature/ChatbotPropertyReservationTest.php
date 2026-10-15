<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityItem;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationPendingApproval;
use App\Notifications\ReservationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ChatbotPropertyReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatbot_submits_property_reservations_to_the_admin_approval_queue(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);
        $facility = $this->createFacility();
        $item = FacilityItem::create([
            'facility_id' => $facility->id,
            'name' => 'Projector',
            'quantity' => 3,
        ]);

        $prompt = $this->actingAs($user)
            ->postJson(route('chatbot.respond'), [
                'message' => 'Reserve 2 projectors in Lecture Room 1 on May 1 2030 from 10 AM to 12 PM',
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

    public function test_chatbot_asks_for_use_and_return_times_when_property_booking_times_are_missing(): void
    {
        $user = User::factory()->create();
        $facility = $this->createFacility();
        FacilityItem::create([
            'facility_id' => $facility->id,
            'name' => 'Projector',
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('chatbot.respond'), [
                'message' => 'I want to reserve a projector in Lecture Room 1 tomorrow',
            ]);

        $response->assertOk();
        $this->assertStringContainsString('What is the purpose', $response->json('reply'));

        $response = $this->postJson(route('chatbot.respond'), ['message' => 'Borrowing for a lecture']);
        $response->assertOk();
        $this->assertStringContainsString('What time will you use the items', $response->json('reply'));
        $this->assertStringContainsString('what time will you return them', strtolower($response->json('reply')));
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
        $facility = $this->createFacility();
        FacilityItem::create([
            'facility_id' => $facility->id,
            'name' => 'Projector',
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('chatbot.respond'), [
                'message' => 'Reserve 2 projectors in Lecture Room 1 on May 1 2030 from 10 AM to 12 PM',
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
