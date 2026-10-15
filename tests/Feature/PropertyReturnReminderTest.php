<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityItem;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\PropertyReturnDue;
use App\Notifications\PropertyReturnNotConfirmed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class PropertyReturnReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_approved_property_reservation_is_notified_once(): void
    {
        $this->travelTo(now()->setDate(2030, 5, 1)->setTime(12, 0));
        $user = User::factory()->create();
        [$reservation, $item] = $this->createPropertyReservation($user, 'approved', '11:00');
        $reservation->items()->attach($item, ['quantity' => 2]);

        $this->assertSame('approved', $reservation->fresh()->status);
        $this->assertSame(
            1,
            Reservation::query()
                ->whereDate('reservation_date', now()->toDateString())
                ->whereTime('end_time', '<=', now()->format('H:i:s'))
                ->whereHas('items')
                ->whereNull('return_reminded_at')
                ->count()
        );

        $this->artisan('property-returns:notify-due')
            ->expectsOutput('Sent 1 property return reminder(s).')
            ->assertSuccessful();
        $this->artisan('property-returns:notify-due')->assertSuccessful();

        $this->assertSame(1, $user->notifications()->where('type', PropertyReturnDue::class)->count());
        $this->assertNotNull($reservation->fresh()->return_reminded_at);
    }

    public function test_pending_or_not_yet_due_property_reservations_are_not_notified(): void
    {
        $this->travelTo(now()->setDate(2030, 5, 1)->setTime(12, 0));
        $pendingUser = User::factory()->create();
        [$pendingReservation, $pendingItem] = $this->createPropertyReservation($pendingUser, 'pending', '11:00');
        $pendingReservation->items()->attach($pendingItem, ['quantity' => 1]);

        $futureUser = User::factory()->create();
        [$futureReservation, $futureItem] = $this->createPropertyReservation($futureUser, 'approved', '13:00');
        $futureReservation->items()->attach($futureItem, ['quantity' => 1]);

        $this->artisan('property-returns:notify-due')->assertSuccessful();

        $this->assertSame(0, $pendingUser->notifications()->count());
        $this->assertSame(0, $futureUser->notifications()->count());
    }

    public function test_borrower_can_confirm_return_and_complete_reservation(): void
    {
        $this->travelTo(now()->setDate(2030, 5, 1)->setTime(12, 0));
        $user = User::factory()->create();
        [$reservation, $item] = $this->createPropertyReservation($user, 'approved', '11:00');
        $reservation->items()->attach($item, ['quantity' => 1]);
        $notification = $this->createReturnNotification($user, $reservation);

        $this->actingAs($user)
            ->post(route('notifications.propertyReturn.respond', $reservation), [
                'notification_id' => $notification->id,
                'response' => 'returned',
            ])
            ->assertRedirect();

        $this->assertSame('completed', $reservation->fresh()->status);
        $this->assertSame('returned', $notification->fresh()->data['response']);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_only_owner_can_respond_and_only_after_return_time(): void
    {
        $this->travelTo(now()->setDate(2030, 5, 1)->setTime(10, 30));
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        [$reservation, $item] = $this->createPropertyReservation($user, 'approved', '11:00');
        $reservation->items()->attach($item, ['quantity' => 1]);
        $notification = $this->createReturnNotification($user, $reservation);

        $this->actingAs($otherUser)
            ->post(route('notifications.propertyReturn.respond', $reservation), [
                'notification_id' => $notification->id,
                'response' => 'returned',
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('notifications.propertyReturn.respond', $reservation), [
                'notification_id' => $notification->id,
                'response' => 'returned',
            ])
            ->assertUnprocessable();

        $this->assertSame('approved', $reservation->fresh()->status);
        $this->assertArrayNotHasKey('response', $notification->fresh()->data);
    }

    public function test_borrower_reporting_items_not_returned_notifies_admins_without_completing(): void
    {
        $this->travelTo(now()->setDate(2030, 5, 1)->setTime(12, 0));
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        [$reservation, $item] = $this->createPropertyReservation($user, 'approved', '11:00');
        $reservation->items()->attach($item, ['quantity' => 1]);
        $notification = $this->createReturnNotification($user, $reservation);

        $this->actingAs($user)
            ->post(route('notifications.propertyReturn.respond', $reservation), [
                'notification_id' => $notification->id,
                'response' => 'not_returned',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('approved', $reservation->fresh()->status);
        $this->assertSame('not_returned', $notification->fresh()->data['response']);
        $this->assertSame(1, $admin->notifications()->where('type', PropertyReturnNotConfirmed::class)->count());

        $this->post(route('notifications.propertyReturn.respond', $reservation), [
            'notification_id' => $notification->id,
            'response' => 'not_returned',
        ])->assertRedirect()->assertSessionHas('info');

        $this->assertSame(1, $admin->notifications()->where('type', PropertyReturnNotConfirmed::class)->count());
    }

    public function test_return_reminder_page_offers_yes_and_no_actions(): void
    {
        $this->travelTo(now()->setDate(2030, 5, 1)->setTime(12, 0));
        $user = User::factory()->create();
        [$reservation, $item] = $this->createPropertyReservation($user, 'approved', '11:00');
        $reservation->items()->attach($item, ['quantity' => 1]);
        $this->createReturnNotification($user, $reservation);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Yes, returned')
            ->assertSee('No, not returned');
    }

    private function createPropertyReservation(User $user, string $status, string $endTime): array
    {
        $facility = Facility::create([
            'name' => 'Lecture Room '.$user->id,
            'building' => 'Main Building',
            'type' => 'Classroom',
            'capacity' => 30,
            'is_active' => true,
        ]);
        $item = FacilityItem::create([
            'facility_id' => $facility->id,
            'name' => 'Projector',
            'quantity' => 3,
        ]);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'reservation_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => $endTime,
            'purpose' => 'Class presentation',
            'status' => $status,
        ]);

        return [$reservation, $item];
    }

    private function createReturnNotification(User $user, Reservation $reservation): DatabaseNotification
    {
        $reservation->load(['facility', 'items']);
        $user->notify(new PropertyReturnDue($reservation));

        return $user->notifications()->where('type', PropertyReturnDue::class)->latest()->firstOrFail();
    }
}
