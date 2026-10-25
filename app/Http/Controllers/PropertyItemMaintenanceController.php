<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FacilityItem;
use App\Models\PropertyItemMaintenanceBlock;
use App\Models\Reservation;
use App\Services\PropertyItemMaintenanceSchedule;
use App\Services\ReservationNotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PropertyItemMaintenanceController extends Controller
{
    public function store(
        Request $request,
        PropertyItemMaintenanceSchedule $maintenanceSchedule,
        ReservationNotificationDispatcher $reservationNotifications
    ) {
        $this->requireAdmin();

        if (! $maintenanceSchedule->isAvailable()) {
            return back()->with('error', 'Property maintenance scheduling is unavailable until its database migration has been applied.');
        }

        $validated = $this->validateBlock($request);
        $isAllDay = $request->boolean('is_all_day');
        $startTime = $isAllDay ? null : $validated['start_time'];
        $endTime = $isAllDay ? null : $validated['end_time'];
        $item = FacilityItem::findOrFail($validated['facility_item_id']);

        [$block, $overlappingReservations] = DB::transaction(function () use (
            $validated,
            $item,
            $isAllDay,
            $startTime,
            $endTime
        ): array {
            $lockedItem = FacilityItem::query()->lockForUpdate()->findOrFail($item->id);
            $block = PropertyItemMaintenanceBlock::create([
                'facility_item_id' => $lockedItem->id,
                'maintenance_date' => $validated['maintenance_date'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_all_day' => $isAllDay,
                'reason' => $validated['reason'],
            ]);

            $overlappingReservations = $this->rejectOverlappingReservations(
                $lockedItem,
                $validated['maintenance_date'],
                $isAllDay,
                $startTime,
                $endTime,
                $validated['reason']
            );

            AuditLog::create([
                'user_id' => Auth::id(),
                'reservation_id' => null,
                'action' => 'property_item_maintenance_created',
                'details' => "Scheduled maintenance for {$item->name} on {$validated['maintenance_date']}: {$validated['reason']}.",
            ]);

            return [$block, $overlappingReservations];
        });

        $this->notifyRejectedReservations($overlappingReservations, $reservationNotifications);

        return redirect()
            ->route('admin.reservation-settings')
            ->with('status', $overlappingReservations->isEmpty()
                ? 'Property item maintenance scheduled successfully.'
                : 'Property item maintenance scheduled. Overlapping reservations were automatically rejected and their owners notified.');
    }

    public function update(
        Request $request,
        PropertyItemMaintenanceBlock $block,
        PropertyItemMaintenanceSchedule $maintenanceSchedule,
        ReservationNotificationDispatcher $reservationNotifications
    ) {
        $this->requireAdmin();

        if (! $maintenanceSchedule->isAvailable()) {
            return back()->with('error', 'Property maintenance scheduling is unavailable until its database migration has been applied.');
        }

        $validated = $this->validateBlock($request);
        $isAllDay = $request->boolean('is_all_day');
        $startTime = $isAllDay ? null : $validated['start_time'];
        $endTime = $isAllDay ? null : $validated['end_time'];
        $item = FacilityItem::findOrFail($validated['facility_item_id']);

        $overlappingReservations = DB::transaction(function () use (
            $block,
            $validated,
            $item,
            $isAllDay,
            $startTime,
            $endTime
        ): Collection {
            $lockedBlock = PropertyItemMaintenanceBlock::query()->lockForUpdate()->findOrFail($block->id);
            $lockedItem = FacilityItem::query()->lockForUpdate()->findOrFail($item->id);
            $lockedBlock->update([
                'facility_item_id' => $lockedItem->id,
                'maintenance_date' => $validated['maintenance_date'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_all_day' => $isAllDay,
                'reason' => $validated['reason'],
            ]);

            $overlappingReservations = $this->rejectOverlappingReservations(
                $lockedItem,
                $validated['maintenance_date'],
                $isAllDay,
                $startTime,
                $endTime,
                $validated['reason']
            );

            AuditLog::create([
                'user_id' => Auth::id(),
                'reservation_id' => null,
                'action' => 'property_item_maintenance_updated',
                'details' => "Updated maintenance for {$item->name} on {$validated['maintenance_date']}: {$validated['reason']}.",
            ]);

            return $overlappingReservations;
        });

        $this->notifyRejectedReservations($overlappingReservations, $reservationNotifications);

        return redirect()
            ->route('admin.reservation-settings')
            ->with('status', $overlappingReservations->isEmpty()
                ? 'Property item maintenance updated successfully.'
                : 'Property item maintenance updated. Overlapping reservations were automatically rejected and their owners notified.');
    }

    public function destroy(Request $request, PropertyItemMaintenanceBlock $block)
    {
        $this->requireAdmin();
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        DB::transaction(function () use ($block): void {
            $lockedBlock = PropertyItemMaintenanceBlock::query()->with('item')->lockForUpdate()->findOrFail($block->id);
            $details = "Removed property item maintenance for {$lockedBlock->item->name} on {$lockedBlock->maintenance_date->format('Y-m-d')}.";
            $lockedBlock->delete();

            AuditLog::create([
                'user_id' => Auth::id(),
                'reservation_id' => null,
                'action' => 'property_item_maintenance_removed',
                'details' => $details,
            ]);
        });

        return redirect()->route('admin.reservation-settings')->with('status', 'Property item maintenance removed.');
    }

    private function validateBlock(Request $request): array
    {
        return $request->validate([
            'facility_item_id' => ['required', 'integer', Rule::exists('facility_items', 'id')],
            'maintenance_date' => ['required', 'date', 'after_or_equal:today'],
            'is_all_day' => ['required', 'boolean'],
            'start_time' => ['nullable', 'required_unless:is_all_day,1', 'date_format:H:i'],
            'end_time' => ['nullable', 'required_unless:is_all_day,1', 'date_format:H:i', 'after:start_time'],
            'reason' => ['required', 'string', 'max:255'],
            'current_password' => ['required', 'current_password'],
        ]);
    }

    private function rejectOverlappingReservations(
        FacilityItem $item,
        string $date,
        bool $isAllDay,
        ?string $startTime,
        ?string $endTime,
        string $reason
    ): Collection {
        $overlappingReservations = Reservation::query()
            ->whereHas('items', fn ($query) => $query->where('facility_items.id', $item->id))
            ->whereDate('reservation_date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->when(! $isAllDay, fn ($query) => $query
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime))
            ->lockForUpdate()
            ->get();

        foreach ($overlappingReservations as $reservation) {
            $details = "Automatically rejected due to property item maintenance for {$item->name}: {$reason}.";
            $reservation->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'admin_remarks' => $details,
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'reservation_id' => $reservation->id,
                'action' => 'rejected',
                'details' => $details,
            ]);
        }

        return $overlappingReservations;
    }

    private function notifyRejectedReservations(
        Collection $reservations,
        ReservationNotificationDispatcher $reservationNotifications
    ): void {
        foreach ($reservations as $reservation) {
            $reservationNotifications->notifyBorrower($reservation);
        }
    }

    private function requireAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->isAdmin(), 403, 'Admin access only.');
    }
}
