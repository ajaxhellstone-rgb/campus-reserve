<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilityItemManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_property_management_and_dashboard_links_to_it(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);
        $facility = Facility::create([
            'name' => 'Lecture Room 1',
            'building' => 'Main Building',
            'type' => 'Classroom',
            'capacity' => 30,
        ]);
        FacilityItem::create([
            'facility_id' => $facility->id,
            'name' => 'Projector',
            'quantity' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText('Facilities')
            ->assertSeeText('Property items')
            ->assertSee(route('admin.facilities.create'))
            ->assertSee(route('admin.property-management.index'));

        $this->get(route('admin.property-management.index'))
            ->assertOk()
            ->assertSee('Projector')
            ->assertSee('Main Building')
            ->assertSee('Admin dashboard navigation')
            ->assertSee('Property items')
            ->assertSee('aria-current="page"', false);

        $this->get(route('admin.facilities.create'))
            ->assertOk()
            ->assertSee('Admin dashboard navigation')
            ->assertSee('Facility Management')
            ->assertSee('aria-current="page"', false);
    }

    public function test_facility_management_omits_amenities_and_facility_booking_shows_item_options(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);
        Facility::create([
            'name' => 'Classroom A',
            'building' => 'Academic Building',
            'type' => 'Classroom',
            'capacity' => 30,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.facilities.create'))
            ->assertOk()
            ->assertDontSee('Amenities');

        $this->get(route('admin.facilities.create'))
            ->assertSee('Classroom A')
            ->assertDontSee('bg-gradient-to-br from-slate-200 to-slate-300');

        $this->get('/calendar/manage')
            ->assertOk()
            ->assertSee('Items included with this facility')
            ->assertSee('facilityItemOptions');
    }

    public function test_facilities_api_returns_identifiers_for_item_availability_lookup(): void
    {
        $facility = Facility::create([
            'name' => 'Lecture Room 1',
            'building' => 'Main Building',
            'type' => 'Classroom',
            'capacity' => 30,
            'is_active' => true,
        ]);

        $this->getJson('/api/facilities')
            ->assertOk()
            ->assertJsonPath('0.id', $facility->id)
            ->assertJsonPath('0.name', $facility->name);
    }

    public function test_admin_can_add_update_and_remove_facility_items(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);
        $facility = Facility::create([
            'name' => 'Lecture Room 1',
            'building' => 'Main Building',
            'type' => 'Classroom',
            'capacity' => 30,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.property-management.items.store'), [
                'current_password' => 'password',
                'facility_id' => $facility->id,
                'name' => 'Projector',
                'quantity' => 2,
            ])
            ->assertRedirect(route('admin.property-management.index'))
            ->assertSessionHas('status', 'Facility item added successfully.');

        $item = FacilityItem::firstOrFail();
        $this->assertDatabaseHas('facility_items', [
            'id' => $item->id,
            'facility_id' => $facility->id,
            'name' => 'Projector',
            'quantity' => 2,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.property-management.items.update', $item), [
                'current_password' => 'password',
                'facility_id' => $facility->id,
                'name' => 'Interactive Display',
                'quantity' => 1,
            ])
            ->assertRedirect(route('admin.property-management.index'))
            ->assertSessionHas('status', 'Facility item updated successfully.');

        $this->assertDatabaseHas('facility_items', [
            'id' => $item->id,
            'name' => 'Interactive Display',
            'quantity' => 1,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.property-management.items.destroy', $item), ['current_password' => 'password'])
            ->assertRedirect(route('admin.property-management.index'))
            ->assertSessionHas('status', 'Facility item removed successfully.');

        $this->assertDatabaseMissing('facility_items', ['id' => $item->id]);
    }

    public function test_non_admin_cannot_access_property_management(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'is_super_admin' => false,
        ]);

        $this->actingAs($user)
            ->get(route('admin.property-management.index'))
            ->assertForbidden();
    }
}
