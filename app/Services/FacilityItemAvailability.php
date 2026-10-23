<?php

namespace App\Services;

use App\Models\FacilityItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FacilityItemAvailability
{
    public function availableQuantity(
        FacilityItem $item,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeReservationId = null
    ): int {
        $reservations = $item->reservations()
            ->where('reservations.status', 'approved')
            ->whereDate('reservations.reservation_date', $date)
            ->where('reservations.start_time', '<', $endTime)
            ->where('reservations.end_time', '>', $startTime);
        if ($excludeReservationId !== null) {
            $reservations->where('reservations.id', '!=', $excludeReservationId);
        }
        $reservedQuantity = $reservations->sum('reservation_items.quantity');

        return max(0, $item->quantity - (int) $reservedQuantity);
    }

    public function availableQuantities(
        iterable $items,
        string $date,
        string $startTime,
        string $endTime
    ): array {
        $quantities = [];
        foreach ($items as $item) {
            $quantities[$item->id] = $item->quantity;
        }

        if ($quantities === []) {
            return [];
        }

        $nextDate = Carbon::parse($date)->addDay()->toDateString();
        $reservedQuantities = DB::table('reservation_items')
            ->join('reservations', 'reservations.id', '=', 'reservation_items.reservation_id')
            ->whereIn('reservation_items.facility_item_id', array_keys($quantities))
            ->where('reservations.status', 'approved')
            ->where('reservations.reservation_date', '>=', $date)
            ->where('reservations.reservation_date', '<', $nextDate)
            ->where('reservations.start_time', '<', $endTime)
            ->where('reservations.end_time', '>', $startTime)
            ->select('reservation_items.facility_item_id')
            ->selectRaw('SUM(reservation_items.quantity) AS reserved_quantity')
            ->groupBy('reservation_items.facility_item_id')
            ->pluck('reserved_quantity', 'facility_item_id');

        foreach ($quantities as $itemId => $quantity) {
            $quantities[$itemId] = max(0, $quantity - (int) ($reservedQuantities[$itemId] ?? 0));
        }

        return $quantities;
    }
}
