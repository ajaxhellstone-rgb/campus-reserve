<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateFacilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_create_duplicate_facility_name_in_same_building(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);

        Facility::create([
            'name' => 'Classroom A',
            'building' => 'Academic Building',
            'type' => 'classroom',
            'capacity' => 30,
            'requires_approval' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.facilities.store'), [
                'name' => 'Classroom A',
                'building' => 'Academic Building',
                'type' => 'Classroom',
                'capacity' => 35,
                'amenities' => 'Projector',
            ])
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseCount('facilities', 1);
    }

    public function test_admin_is_redirected_to_manage_facility_page_with_success_message_after_creation(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.facilities.store'), [
                'name' => 'Computer Lab',
                'building' => 'Engineering Hall',
                'type' => 'Laboratory',
                'capacity' => 24,
                'amenities' => 'Projector, Whiteboard',
            ])
            ->assertRedirect(route('admin.facilities.create'))
            ->assertSessionHas('status', 'Facility created successfully.');

        $this->assertDatabaseHas('facilities', [
            'name' => 'Computer Lab',
            'building' => 'Engineering Hall',
        ]);
    }

    public function test_admin_can_delete_a_facility_from_manage_facilities_page(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);

        $facility = Facility::create([
            'name' => 'Lecture Hall',
            'building' => 'Science Center',
            'type' => 'Auditorium',
            'capacity' => 80,
            'requires_approval' => false,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.facilities.destroy', $facility))
            ->assertRedirect(route('admin.facilities.create'))
            ->assertSessionHas('status', 'Facility removed successfully.');

        $this->assertDatabaseMissing('facilities', [
            'id' => $facility->id,
        ]);
    }

    public function test_admin_can_update_a_facility_from_manage_facilities_page(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);

        $facility = Facility::create([
            'name' => 'Old Lab',
            'building' => 'Engineering Hall',
            'type' => 'Laboratory',
            'capacity' => 18,
            'requires_approval' => false,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.facilities.update', $facility), [
                'name' => 'Updated Lab',
                'building' => 'Engineering Hall',
                'type' => 'Laboratory',
                'capacity' => 22,
                'amenities' => 'Projector, Whiteboard',
            ])
            ->assertRedirect(route('admin.facilities.create'))
            ->assertSessionHas('status', 'Facility updated successfully.');

        $this->assertDatabaseHas('facilities', [
            'id' => $facility->id,
            'name' => 'Updated Lab',
            'capacity' => 22,
        ]);
    }
}
