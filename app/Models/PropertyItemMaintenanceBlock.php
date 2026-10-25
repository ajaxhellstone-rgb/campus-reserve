<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyItemMaintenanceBlock extends Model
{
    protected $fillable = [
        'facility_item_id',
        'maintenance_date',
        'start_time',
        'end_time',
        'is_all_day',
        'reason',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'is_all_day' => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(FacilityItem::class, 'facility_item_id');
    }
}
