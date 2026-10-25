<?php

namespace App\Services;

use App\Models\PropertyItemMaintenanceBlock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PropertyItemMaintenanceSchedule
{
    private ?bool $tableAvailable = null;

    public function isAvailable(): bool
    {
        if ($this->tableAvailable === null) {
            $this->tableAvailable = Schema::hasTable('property_item_maintenance_blocks');
        }

        return $this->tableAvailable;
    }

    public function conflictingBlock(
        int $itemId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeBlockId = null
    ): ?PropertyItemMaintenanceBlock {
        if (! $this->isAvailable()) {
            return null;
        }

        return PropertyItemMaintenanceBlock::query()
            ->where('facility_item_id', $itemId)
            ->whereDate('maintenance_date', $date)
            ->where(function ($query) use ($startTime, $endTime): void {
                $query->where('is_all_day', true)
                    ->orWhere(function ($query) use ($startTime, $endTime): void {
                        $query->where('start_time', '<', $endTime)
                            ->where('end_time', '>', $startTime);
                    });
            })
            ->when($excludeBlockId !== null, fn ($query) => $query->where('id', '!=', $excludeBlockId))
            ->first();
    }

    public function activeItemIds(): Collection
    {
        if (! $this->isAvailable()) {
            return collect();
        }

        $now = now();

        return PropertyItemMaintenanceBlock::query()
            ->whereDate('maintenance_date', $now->toDateString())
            ->where(function ($query) use ($now): void {
                $query->where('is_all_day', true)
                    ->orWhere(function ($query) use ($now): void {
                        $query->where('start_time', '<=', $now->format('H:i:s'))
                            ->where('end_time', '>', $now->format('H:i:s'));
                    });
            })
            ->distinct()
            ->pluck('facility_item_id');
    }

    public function validationMessage(PropertyItemMaintenanceBlock $block): string
    {
        return "This property item is unavailable: {$block->reason}.";
    }
}
