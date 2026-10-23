<?php

namespace App\Services;

use App\Models\FacilityMaintenanceBlock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

class FacilityMaintenanceSchedule
{
    public function isAvailable(): bool
    {
        return Schema::hasTable('facility_maintenance_blocks');
    }

    public function conflictingBlock(int $facilityId, string $date, string $startTime, string $endTime): ?FacilityMaintenanceBlock
    {
        if (! $this->isAvailable()) {
            return null;
        }

        return FacilityMaintenanceBlock::query()
            ->where('facility_id', $facilityId)
            ->whereDate('maintenance_date', $date)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('is_all_day', true)
                    ->orWhere(function ($query) use ($startTime, $endTime) {
                        $query->where('start_time', '<', $endTime)
                            ->where('end_time', '>', $startTime);
                    });
            })
            ->first();
    }

    public function forDateRange(string $start, string $end, ?string $facilityName = null): Collection
    {
        if (! $this->isAvailable()) {
            return new Collection();
        }

        return FacilityMaintenanceBlock::query()
            ->with('facility')
            ->where('maintenance_date', '>=', $start)
            ->where('maintenance_date', '<', $end)
            ->when($facilityName, fn ($query) => $query->whereHas(
                'facility',
                fn ($facilityQuery) => $facilityQuery->where('name', $facilityName)
            ))
            ->orderBy('maintenance_date')
            ->orderBy('start_time')
            ->get();
    }

    public function validationMessage(FacilityMaintenanceBlock $block): string
    {
        return "This facility is unavailable: {$block->reason}.";
    }
}
