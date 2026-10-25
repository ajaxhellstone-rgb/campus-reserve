<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityItem;
use App\Models\PropertyItemMaintenanceBlock;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PropertyItemMaintenanceSchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2030-04-01 10:30'));
    }

    public function test_scheduling_item_maintenance_rejects_and_notifies_overlapping_reservations(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $item = $this->createItem();
        $pending = $this->createItemReservation($user, $item, 'pending', '09:00', '10:30');
        $approved = $this->createItemReservation($user, $item, 'approved', '10:30', '12:00');
        $unaffected = $this->createItemReservation($user, $item, 'approved', '12:00', '13:00');

        $this->actingAs($admin)
            ->post(route('admin.reservation-settings.property-maintenance.store'), [
                'facility_item_id' => $item->id,
                'maintenance_date' => '2030-05-01',
                'is_all_day' => '0',
                'start_time' => '10:00',
                'end_time' => '11:00',
                'reason' => 'Repair',
                'current_password' => 'password',
            ])
            ->assertRedirect(route('admin.reservation-settings'))
            ->assertSessionHas('status', 'Property item maintenance scheduled. Overlapping reservations were automatically rejected and their owners notified.');

        $this->assertDatabaseHas('property_item_maintenance_blocks', [
            'facility_item_id' => $item->id,
            'maintenance_date' => '2030-05-01 00:00:00',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'reason' => 'Repair',
        ]);
        $this->assertSame('rejected', $pending->fresh()->status);
        $this->assertSame('rejected', $approved->fresh()->status);
        $this->assertSame('approved', $unaffected->fresh()->status);
        $this->assertNotNull($approved->fresh()->rejected_at);
        $this->assertDatabaseHas('audit_logs', [
            'reservation_id' => $approved->id,
            'action' => 'rejected',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'property_item_maintenance_created',
        ]);
        Notification::assertSentTo($user, ReservationStatusChanged::class);
    }

    public function test_property_booking_and_availability_respect_maintenance_overlap(): void
    {
        $user = User::factory()->create();
        $item = $this->createItem();
        PropertyItemMaintenanceBlock::create([
            'facility_item_id' => $item->id,
            'maintenance_date' => '2030-05-01',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'is_all_day' => false,
            'reason' => 'Inspection',
        ]);

        $this->actingAs($user)
            ->getJson(route('api.property-items.availability', [
                'date' => '2030-05-01',
                'start_time' => '11:00',
                'end_time' => '13:00',
            ]))
            ->assertOk()
            ->assertJsonPath('items.0.available', 0)
            ->assertJsonPath('items.0.maintenance_reason', 'Inspection');

        $this->postJson('/api/calendar/reserve', [
            'purpose' => 'Presentation',
            'date' => '2030-05-01',
            'start_time' => '11:00',
            'end_time' => '13:00',
            'items' => [[
                'facility_item_id' => $item->id,
                'quantity' => 1,
            ]],
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'property_item_maintenance')
            ->assertJsonPath('error', 'This property item is unavailable: Inspection.');

        $this->postJson('/api/calendar/reserve', [
            'purpose' => 'Presentation',
            'date' => '2030-05-01',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'items' => [[
                'facility_item_id' => $item->id,
                'quantity' => 1,
            ]],
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_available_property_items_hide_only_during_an_active_maintenance_window(): void
    {
        $user = User::factory()->create();
        $activeItem = $this->createItem('Active item');
        $futureItem = $this->createItem('Future item');

        PropertyItemMaintenanceBlock::create([
            'facility_item_id' => $activeItem->id,
            'maintenance_date' => '2030-04-01',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'is_all_day' => false,
            'reason' => 'Currently being repaired',
        ]);
        PropertyItemMaintenanceBlock::create([
            'facility_item_id' => $futureItem->id,
            'maintenance_date' => '2030-04-02',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'is_all_day' => false,
            'reason' => 'Scheduled repair',
        ]);

        $this->actingAs($user)
            ->getJson(route('api.facilities.property-items'))
            ->assertOk()
            ->assertJsonMissing(['id' => $activeItem->id])
            ->assertJsonFragment(['id' => $futureItem->id, 'name' => 'Future item', 'quantity' => 2]);

        $this->travelTo(Carbon::parse('2030-04-01 11:00'));
        $this->getJson(route('api.facilities.property-items'))
            ->assertOk()
            ->assertJsonFragment(['id' => $activeItem->id, 'name' => 'Active item', 'quantity' => 2]);
    }

    public function test_admin_cannot_approve_a_property_reservation_during_maintenance(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $item = $this->createItem();
        $reservation = $this->createItemReservation($user, $item, 'pending', '10:00', '11:00');
        PropertyItemMaintenanceBlock::create([
            'facility_item_id' => $item->id,
            'maintenance_date' => '2030-05-01',
            'start_time' => '10:30',
            'end_time' => '12:00',
            'is_all_day' => false,
            'reason' => 'Emergency repair',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.reservations.approve', $reservation), [
                'current_password' => 'password',
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Cannot approve this reservation because Projector is under maintenance: Emergency repair.');

        $this->assertSame('pending', $reservation->fresh()->status);
    }

    public function test_chatbot_cannot_submit_a_property_reservation_during_maintenance(): void
    {
        $user = User::factory()->create();
        $item = $this->createItem();
        PropertyItemMaintenanceBlock::create([
            'facility_item_id' => $item->id,
            'maintenance_date' => '2030-05-01',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'is_all_day' => false,
            'reason' => 'Inspection',
        ]);

        $this->actingAs($user)
            ->postJson(route('chatbot.respond'), [
                'message' => 'Reserve 1 projector on May 1 2030 from 10 AM to 12 PM',
            ])
            ->assertOk()
            ->assertJsonPath('reply', 'What is the purpose of your property reservation?');

        $this->postJson(route('chatbot.respond'), ['message' => 'Class presentation'])
            ->assertOk()
            ->assertJsonPath('reply', 'Projector has only 0 available on May 1, 2030 from 10:00 AM to 12:00 PM. Tell me a smaller quantity or another date and time.');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_admin_can_edit_and_remove_property_item_maintenance_with_password_confirmation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $item = $this->createItem();
        $reservation = $this->createItemReservation($user, $item, 'pending', '09:00', '10:00', '2030-05-02');
        $block = PropertyItemMaintenanceBlock::create([
            'facility_item_id' => $item->id,
            'maintenance_date' => '2030-05-01',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'is_all_day' => false,
            'reason' => 'Old reason',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reservation-settings'))
            ->assertOk()
            ->assertSee('Property item maintenance')
            ->assertSee('data-edit-property-maintenance', false);

        $this->put(route('admin.reservation-settings.property-maintenance.update', $block), [
            'facility_item_id' => $item->id,
            'maintenance_date' => '2030-05-02',
            'is_all_day' => '1',
            'reason' => 'Updated reason',
            'current_password' => 'password',
        ])
            ->assertRedirect(route('admin.reservation-settings'))
            ->assertSessionHas('status', 'Property item maintenance updated. Overlapping reservations were automatically rejected and their owners notified.');

        $this->assertSame('rejected', $reservation->fresh()->status);
        $this->assertDatabaseHas('property_item_maintenance_blocks', [
            'id' => $block->id,
            'maintenance_date' => '2030-05-02 00:00:00',
            'is_all_day' => 1,
            'reason' => 'Updated reason',
        ]);

        $this->delete(route('admin.reservation-settings.property-maintenance.destroy', $block), [
            'current_password' => 'password',
        ])
            ->assertRedirect(route('admin.reservation-settings'))
            ->assertSessionHas('status', 'Property item maintenance removed.');

        $this->assertDatabaseMissing('property_item_maintenance_blocks', ['id' => $block->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'property_item_maintenance_updated']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'property_item_maintenance_removed']);
    }

    private function createItem(string $name = 'Projector'): FacilityItem
    {
        return FacilityItem::create([
            'facility_id' => null,
            'name' => $name,
            'quantity' => 2,
        ]);
    }

    private function createItemReservation(
        User $user,
        FacilityItem $item,
        string $status,
        string $start,
        string $end,
        string $date = '2030-05-01'
    ): Reservation {
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'facility_id' => null,
            'reservation_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'purpose' => 'Presentation',
            'status' => $status,
        ]);
        $reservation->items()->attach($item, ['quantity' => 1]);

        return $reservation;
    }
}
