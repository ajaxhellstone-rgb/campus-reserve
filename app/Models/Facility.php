<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
    /**
     * Fields I can assign when creating/updating a facility.
     */
    protected $fillable = [
        'name',
        'building',
        'room_number',
        'type',
        'capacity',
        'description',
        'image',
        'is_active',
        'requires_approval',
    ];

    /**
     * Tell Laravel what type each field is (arrays, booleans, etc.)
     */
    protected $casts = [
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
    ];

    /**
     * Link to all reservations for this facility.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FacilityItem::class);
    }

    /**
     * Get a readable name for the facility type.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'classroom' => 'Classroom',
            'laboratory' => 'Laboratory',
            'conference_room' => 'Conference Room',
            'auditorium' => 'Auditorium',
            'sports_hall' => 'Sports Hall',
            'study_room' => 'Study Room',
            'other' => 'Other',
            default => ucfirst($this->type),
        };
    }

    /**
     * Only return active facilities.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if this room is free at a specific date and time.
     */
    public function isAvailable($date, $startTime, $endTime, $excludeReservationId = null): bool
    {
        // I only block approvals and calendar bookings against already approved reservations.
        // Pending reservations may overlap, but they are not shown on the calendar until approved.
        $query = $this->reservations()
            ->whereDate('reservation_date', $date)
            ->where('status', 'approved')
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->count() === 0;
    }
}
