<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reservation extends Model
{
    /**
     * Fields I can set when creating/updating a reservation.
     */
    protected $fillable = [
        'user_id',
        'facility_id',
        'reservation_date',
        'start_time',
        'end_time',
        'purpose',
        'notes',
        'status',
        'admin_remarks',
        'attendees_count',
    ];

    /**
     * Here I tell Laravel what type each field is (dates, times, etc.)
     */
    protected $casts = [
        'reservation_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    /**
     * Link to the user who made this reservation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Link to the facility being reserved.
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(FacilityItem::class, 'reservation_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Get a nice label and color for the current status.
     */
    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'pending' => ['label' => 'Pending', 'class' => 'bg-indigo-100 text-indigo-800'],
            'approved' => ['label' => 'Approved', 'class' => 'bg-green-100 text-green-800'],
            'rejected' => ['label' => 'Rejected', 'class' => 'bg-red-100 text-red-800'],
            'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-gray-100 text-gray-800'],
            'completed' => ['label' => 'Completed', 'class' => 'bg-blue-100 text-blue-800'],
            default => ['label' => ucfirst($this->status), 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    /**
     * Scope to get upcoming reservations.
     */
    public function scopeUpcoming($query)
    {
        // Query only reservations that are still active and not in the past.
        return $query->where('reservation_date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('reservation_date')
            ->orderBy('start_time');
    }

    /**
     * Scope to get past/completed reservations.
     */
    public function scopeCompleted($query)
    {
        return $query->where(function ($q) {
            $q->where('reservation_date', '<', now()->toDateString())
                ->orWhere('status', 'completed');
        });
    }

    /**
     * Check if reservation can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        // A reservation can only be cancelled if it is pending or approved
        // and its date is today or in the future.
        return in_array($this->status, ['pending', 'approved']) 
            && $this->reservation_date >= now()->toDateString();
    }
}
