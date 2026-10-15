<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationStatusChanged;
use App\Services\ReservationNotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReservationEmailNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_notifications_are_disabled_by_default_and_can_be_enabled_in_profile(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->email_notifications);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Email me about my reservations');

        $this->actingAs($user)
            ->post(route('profile.update'), [
                'name' => $user->name,
                'email_notifications' => '1',
            ])
            ->assertRedirect();

        $this->assertTrue($user->fresh()->email_notifications);

        $this->actingAs($user)
            ->post(route('profile.update'), ['name' => $user->name])
            ->assertRedirect();

        $this->assertFalse($user->fresh()->email_notifications);
    }

    public function test_email_notification_toggle_saves_immediately_without_submitting_the_profile_form(): void
    {
        $user = User::factory()->create(['email_notifications' => false]);

        $this->actingAs($user)
            ->postJson(route('profile.email-notifications.update'), [
                'email_notifications' => true,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'Email notifications enabled.');

        $this->assertTrue($user->fresh()->email_notifications);

        $this->postJson(route('profile.email-notifications.update'), [
            'email_notifications' => false,
        ])
            ->assertOk()
            ->assertJsonPath('status', 'Email notifications disabled.');

        $this->assertFalse($user->fresh()->email_notifications);
    }

    public function test_reservation_update_notifications_use_database_only_when_email_is_disabled(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_notifications' => false]);
        $reservation = $this->createReservation($user);

        app(ReservationNotificationDispatcher::class)->notifyBorrower($reservation);

        Notification::assertSentTo($user, ReservationStatusChanged::class, function ($notification, $channels) {
            return $channels === ['database']
                && str_contains($notification->toArray(null)['message'], 'is pending and awaiting approval');
        });
        Notification::assertSentToTimes($user, ReservationStatusChanged::class, 1);
    }

    public function test_opted_in_users_receive_reservation_updates_by_database_and_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_notifications' => true]);
        $reservation = $this->createReservation($user);

        app(ReservationNotificationDispatcher::class)->notifyBorrower($reservation);

        Notification::assertSentTo($user, ReservationStatusChanged::class, function ($notification, $channels) {
            return $channels === ['database'];
        });
        Notification::assertSentTo($user, ReservationStatusChanged::class, function ($notification, $channels) use ($user) {
            return $channels === ['mail']
                && $notification->toMail($user)->subject === 'Reservation Pending - Campus Reserve';
        });
        Notification::assertSentToTimes($user, ReservationStatusChanged::class, 2);
    }

    private function createReservation(User $user): Reservation
    {
        $facility = Facility::create([
            'name' => 'Lecture Room 1',
            'building' => 'Main Building',
            'type' => 'Classroom',
            'capacity' => 30,
            'is_active' => true,
        ]);

        return Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'reservation_date' => '2030-05-01',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'purpose' => 'Class',
            'status' => 'pending',
        ]);
    }
}
