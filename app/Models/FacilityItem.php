<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FacilityItem extends Model
{
    protected $fillable = [
        'facility_id',
        'name',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class, 'reservation_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
