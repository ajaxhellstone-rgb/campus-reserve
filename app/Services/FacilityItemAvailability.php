<?php

namespace App\Services;

use App\Models\FacilityItem;

class FacilityItemAvailability
{
    public function availableQuantity(
        FacilityItem $item,
        string $date,
        string $startTime,
        string $endTime
    ): int {
        $reservedQuantity = $item->reservations()
            ->where('reservations.status', 'approved')
            ->whereDate('reservations.reservation_date', $date)
            ->where('reservations.start_time', '<', $endTime)
            ->where('reservations.end_time', '>', $startTime)
            ->sum('reservation_items.quantity');

        return max(0, $item->quantity - (int) $reservedQuantity);
    }
}
