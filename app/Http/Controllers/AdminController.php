<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Facility;
use App\Models\FacilityMaintenanceBlock;
use App\Models\FacilityItem;
use App\Models\Reservation;
use App\Services\FacilityItemAvailability;
use App\Services\FacilityMaintenanceSchedule;
use App\Services\ReservationNotificationDispatcher;
use App\Services\ReservationTimeWindow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    private function requireAdmin()
    {
        // I check that the user is logged in and is an admin
        // If not, I block them from accessing this page
        if (! Auth::check() || ! Auth::user()->isAdmin()) {
            abort(403, 'Admin access only.');
        }
    }

    private function requireSuperAdmin()
    {
        // Only the head admin can access super-admin features
        // I block everyone else
        if (! Auth::check() || ! Auth::user()->isSuperAdmin()) {
            abort(403, 'Head admin access only.');
        }
    }

    public function dashboard(Request $request)
    {
        // Show the admin dashboard with pending requests, recent activity, and user search
        $this->requireAdmin();

        $search = trim($request->get('search', ''));
        $role = $request->validate([
            'role' => ['nullable', 'in:all,user,admin,super_admin'],
        ])['role'] ?? 'all';
        $users = \App\Models\User::query();

        if ($role === 'user') {
            $users->where('is_admin', false)->where('is_super_admin', false);
        } elseif ($role === 'admin') {
            $users->where('is_admin', true)->where('is_super_admin', false);
        } elseif ($role === 'super_admin') {
            $users->where('is_super_admin', true);
        }

        if ($search !== '') {
            $users->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");

                if (filter_var($search, FILTER_VALIDATE_EMAIL)) {
                    $query->orWhere('email_hash', hash('sha256', strtolower($search)));
                }
            });
        }

        $users = $users->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return response()->view('admin.partials.members', compact('users', 'search', 'role'));
        }

        $pendingReservations = Reservation::with(['user', 'facility', 'items'])
            ->where('status', 'pending')
            ->orderBy('reservation_date')
            ->orderBy('start_time')
            ->paginate(15, ['*'], 'pendingPage')
            ->withQueryString();

        $recentReservations = Reservation::with(['user', 'facility'])
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('updated_at', 'desc')
            ->take(20)
            ->get();

        $reservationDecisionTotals = Reservation::query()
            ->selectRaw('status, COUNT(*) as total')
            ->whereIn('status', ['approved', 'rejected'])
            ->groupBy('status')
            ->pluck('total', 'status');
        $approvedReservationCount = (int) $reservationDecisionTotals->get('approved', 0);
        $rejectedReservationCount = (int) $reservationDecisionTotals->get('rejected', 0);

        $auditLogs = AuditLog::with(['user', 'reservation.facility'])
            ->latest()
            ->take(25)
            ->get();

        return view('admin.dashboard', compact(
            'pendingReservations',
            'recentReservations',
            'approvedReservationCount',
            'rejectedReservationCount',
            'auditLogs',
            'users',
            'search',
            'role',
        ));
    }

    private function reservationActionResponse(Request $request, string $message, ?AuditLog $auditLog = null)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status' => $message,
                'pending_count' => Reservation::where('status', 'pending')->count(),
                'recent_decision_count' => min(20, Reservation::whereIn('status', ['approved', 'rejected'])->count()),
                'audit_log_count' => min(25, AuditLog::count()),
                'audit_log' => $auditLog ? [
                    'action' => $auditLog->action,
                    'details' => $auditLog->details,
                    'created_at' => $auditLog->created_at->format('M d, Y H:i:s'),
                    'user' => Auth::user()->name,
                ] : null,
            ]);
        }

        return back()->with('status', $message);
    }

    public function createFacility()
    {
        $this->requireAdmin();

        $facilities = Facility::query()
            ->orderBy('name')
            ->get()
            ->unique(fn ($facility) => strtolower(trim($facility->name)) . '|' . strtolower(trim($facility->building)))
            ->values()
            ->map(function ($facility) {
                $facility->description = $facility->description ?: (
                    $facility->building
                        ? $facility->building . ' campus facility.'
                        : 'Campus facility.'
                );

                return $facility;
            });

        return view('admin.facilities.create', compact('facilities'));
    }

    public function storeFacility(Request $request)
    {
        $this->requireAdmin();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('facilities')->where(fn ($query) => $query->where('building', trim($request->input('building', '')))),
            ],
            'building' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'min:1'],
            'requires_approval' => ['sometimes', 'boolean'],
        ]);

        $validated['requires_approval'] = $request->boolean('requires_approval');
        $facility = Facility::create($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => null,
            'action' => 'facility_created',
            'details' => "Created facility {$facility->name} in {$facility->building}.",
        ]);

        return redirect()->route('admin.facilities.create')->with('status', 'Facility created successfully.');
    }

    public function updateFacility(Request $request, Facility $facility)
    {
        $this->requireAdmin();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('facilities')->where(fn ($query) => $query->where('building', trim($request->input('building', ''))))->ignore($facility->id),
            ],
            'building' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'min:1'],
            'requires_approval' => ['sometimes', 'boolean'],
        ]);

        $validated['requires_approval'] = $request->boolean('requires_approval');

        $facility->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => null,
            'action' => 'facility_updated',
            'details' => "Updated facility {$facility->name} in {$facility->building}.",
        ]);

        return redirect()->route('admin.facilities.create')->with('status', 'Facility updated successfully.');
    }

    public function destroyFacility(Facility $facility)
    {
        $this->requireAdmin();

        $facilityName = $facility->name;
        $facility->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => null,
            'action' => 'facility_deleted',
            'details' => "Removed facility {$facilityName}.",
        ]);

        return redirect()->route('admin.facilities.create')->with('status', 'Facility removed successfully.');
    }

    public function propertyManagement()
    {
        $this->requireAdmin();

        $items = FacilityItem::orderBy('name')->get();

        return view('admin.property-management', compact('items'));
    }

    public function reservationSettings(
        ReservationTimeWindow $reservationTimeWindow,
        FacilityMaintenanceSchedule $maintenanceSchedule
    )
    {
        $this->requireAdmin();

        $reservationHours = $reservationTimeWindow->current();
        $settingsStorageAvailable = $reservationTimeWindow->isAvailable();
        $maintenanceStorageAvailable = $maintenanceSchedule->isAvailable();
        $facilities = Facility::active()->orderBy('name')->get();
        $maintenanceBlocks = $maintenanceStorageAvailable
            ? FacilityMaintenanceBlock::with('facility')
                ->whereDate('maintenance_date', '>=', today())
                ->orderBy('maintenance_date')
                ->orderBy('start_time')
                ->get()
            : collect();

        return view('admin.reservation-settings', compact(
            'reservationHours',
            'settingsStorageAvailable',
            'maintenanceStorageAvailable',
            'facilities',
            'maintenanceBlocks'
        ));
    }

    public function updateReservationSettings(Request $request, ReservationTimeWindow $reservationTimeWindow)
    {
        $this->requireAdmin();

        if (! $reservationTimeWindow->isAvailable()) {
            return back()->with('error', 'Reservation settings cannot be saved until the reservation settings database migration has been applied.');
        }

        $validated = $request->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'current_password' => ['required', 'current_password'],
        ]);

        $reservationTimeWindow->update($validated['start_time'], $validated['end_time']);

        return redirect()
            ->route('admin.reservation-settings')
            ->with('status', 'Reservation hours updated successfully.');
    }

    public function storeMaintenanceBlock(
        Request $request,
        FacilityMaintenanceSchedule $maintenanceSchedule,
        ReservationNotificationDispatcher $reservationNotifications
    ) {
        $this->requireAdmin();

        if (! $maintenanceSchedule->isAvailable()) {
            return back()->with('error', 'Maintenance scheduling is unavailable until its database migration has been applied.');
        }

        $validated = $request->validate([
            'facility_id' => ['required', 'integer', Rule::exists('facilities', 'id')->where('is_active', true)],
            'maintenance_date' => ['required', 'date', 'after_or_equal:today'],
            'is_all_day' => ['required', 'boolean'],
            'start_time' => ['nullable', 'required_unless:is_all_day,1', 'date_format:H:i'],
            'end_time' => ['nullable', 'required_unless:is_all_day,1', 'date_format:H:i', 'after:start_time'],
            'reason' => ['required', 'string', 'max:255'],
            'current_password' => ['required', 'current_password'],
        ]);

        $isAllDay = $request->boolean('is_all_day');
        $startTime = $isAllDay ? null : $validated['start_time'];
        $endTime = $isAllDay ? null : $validated['end_time'];
        $facility = Facility::findOrFail($validated['facility_id']);

        [$block, $overlappingReservations] = DB::transaction(function () use (
            $validated,
            $facility,
            $isAllDay,
            $startTime,
            $endTime
        ) {
            $block = FacilityMaintenanceBlock::create([
                'facility_id' => $facility->id,
                'maintenance_date' => $validated['maintenance_date'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_all_day' => $isAllDay,
                'reason' => $validated['reason'],
            ]);

            $overlappingReservations = Reservation::query()
                ->where('facility_id', $facility->id)
                ->whereDate('reservation_date', $validated['maintenance_date'])
                ->whereIn('status', ['pending', 'approved'])
                ->when(! $isAllDay, fn ($query) => $query
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime))
                ->lockForUpdate()
                ->get();

            foreach ($overlappingReservations as $reservation) {
                $reservation->update([
                    'status' => 'rejected',
                    'rejected_at' => now(),
                    'admin_remarks' => "Automatically rejected due to facility maintenance: {$validated['reason']}.",
                ]);

                AuditLog::create([
                    'user_id' => Auth::id(),
                    'reservation_id' => $reservation->id,
                    'action' => 'rejected',
                    'details' => "Automatically rejected due to facility maintenance: {$validated['reason']}.",
                ]);
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'reservation_id' => null,
                'action' => 'facility_maintenance_created',
                'details' => "Scheduled maintenance for {$facility->name} on {$validated['maintenance_date']}: {$validated['reason']}.",
            ]);

            return [$block, $overlappingReservations];
        });

        foreach ($overlappingReservations as $reservation) {
            $reservationNotifications->notifyBorrower($reservation);
        }

        return redirect()
            ->route('admin.reservation-settings')
            ->with('status', $overlappingReservations->isEmpty()
                ? 'Maintenance block scheduled successfully.'
                : 'Maintenance block scheduled. Overlapping reservations were automatically rejected and their owners notified.');
    }

    public function destroyMaintenanceBlock(Request $request, FacilityMaintenanceBlock $block)
    {
        $this->requireAdmin();
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $facilityName = $block->facility->name;
        $description = "Removed facility maintenance block for {$facilityName} on {$block->maintenance_date->format('Y-m-d')}.";
        $block->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => null,
            'action' => 'facility_maintenance_removed',
            'details' => $description,
        ]);

        return redirect()->route('admin.reservation-settings')->with('status', 'Maintenance block removed.');
    }

    public function storeFacilityItem(Request $request)
    {
        $this->requireAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $item = FacilityItem::create($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => null,
            'action' => 'facility_item_created',
            'details' => "Added property item {$item->name} (quantity: {$item->quantity}).",
        ]);

        return redirect()->route('admin.property-management.index')->with('status', 'Facility item added successfully.');
    }

    public function updateFacilityItem(Request $request, FacilityItem $item)
    {
        $this->requireAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $item->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => null,
            'action' => 'facility_item_updated',
            'details' => "Updated property item {$item->name} (quantity: {$item->quantity}).",
        ]);

        return redirect()->route('admin.property-management.index')->with('status', 'Facility item updated successfully.');
    }

    public function destroyFacilityItem(FacilityItem $item)
    {
        $this->requireAdmin();

        $itemName = $item->name;
        $item->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => null,
            'action' => 'facility_item_deleted',
            'details' => "Removed property item {$itemName}.",
        ]);

        return redirect()->route('admin.property-management.index')->with('status', 'Facility item removed successfully.');
    }

    public function approve(
        Request $request,
        Reservation $reservation,
        FacilityItemAvailability $itemAvailability,
        ReservationNotificationDispatcher $reservationNotifications,
        ReservationTimeWindow $reservationTimeWindow,
        FacilityMaintenanceSchedule $maintenanceSchedule
    )
    {
        // Mark the reservation as approved and send the user a notification
        $this->requireAdmin();

        if ($reservation->status !== 'pending') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Only pending reservations can be approved.'], 409);
            }

            if (! $reservationTimeWindow->allows(
                $reservation->start_time->format('H:i'),
                $reservation->end_time->format('H:i')
            )) {
                $approvalError = $reservationTimeWindow->validationMessage();

                if ($request->expectsJson()) {
                    return response()->json(['message' => $approvalError], 409);
                }

                return back()->with('error', $approvalError);
            }

            return back()->with('error', 'Only pending reservations can be approved.');
        }

        $approvalError = DB::transaction(function () use ($request, $reservation, $itemAvailability, $maintenanceSchedule) {
            if ($reservation->facility_id !== null) {
                $maintenanceBlock = $maintenanceSchedule->conflictingBlock(
                    $reservation->facility_id,
                    $reservation->reservation_date->format('Y-m-d'),
                    $reservation->start_time->format('H:i'),
                    $reservation->end_time->format('H:i')
                );

                if ($maintenanceBlock) {
                    return $maintenanceSchedule->validationMessage($maintenanceBlock);
                }

                $conflictingApproved = Reservation::where('facility_id', $reservation->facility_id)
                    ->where('reservation_date', $reservation->reservation_date)
                    ->where('status', 'approved')
                    ->where(function ($q) use ($reservation) {
                        $q->whereBetween('start_time', [$reservation->start_time, $reservation->end_time])
                            ->orWhereBetween('end_time', [$reservation->start_time, $reservation->end_time])
                            ->orWhere(function ($q2) use ($reservation) {
                                $q2->where('start_time', '<=', $reservation->start_time)
                                    ->where('end_time', '>=', $reservation->end_time);
                            });
                    })
                    ->exists();

                if ($conflictingApproved) {
                    return 'Cannot approve this reservation because an approved booking already occupies the same room and time slot. Please reject or reschedule it first.';
                }
            }

            $reservedItems = $reservation->items()->orderBy('facility_items.id')->get();
            if ($reservation->facility_id === null && $reservedItems->isEmpty()) {
                return 'Cannot approve this reservation because it has no facility or property items.';
            }
            $lockedItems = FacilityItem::query()
                ->whereIn('id', $reservedItems->modelKeys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($reservedItems as $item) {
                    $lockedItem = $lockedItems->get($item->id);
                    if (!$lockedItem) {
                        return 'Cannot approve this reservation because one of its facility items is no longer available.';
                    }
                    $availableQuantity = $itemAvailability->availableQuantity(
                        $lockedItem,
                        $reservation->reservation_date->format('Y-m-d'),
                        $reservation->start_time->format('H:i'),
                        $reservation->end_time->format('H:i')
                );

                if ($item->pivot->quantity > $availableQuantity) {
                    return "Cannot approve this reservation because only {$availableQuantity} of {$item->name} remain available for this date and time.";
                }
            }

            $reservation->update([
                'status' => 'approved',
                'admin_remarks' => $request->input('remarks', 'Approved by admin.'),
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'reservation_id' => $reservation->id,
                'action' => 'approved',
                'details' => 'Reservation approved by admin',
            ]);

            return null;
        });

        if ($approvalError) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $approvalError], 409);
            }

            return back()->with('error', $approvalError);
        }

        $reservationNotifications->notifyBorrower($reservation);

        return $this->reservationActionResponse(
            $request,
            'Reservation approved successfully.',
            AuditLog::where('reservation_id', $reservation->id)->where('action', 'approved')->latest()->first()
        );
    }

    public function reject(
        Request $request,
        Reservation $reservation,
        ReservationNotificationDispatcher $reservationNotifications
    )
    {
        // Mark the reservation as rejected and let the user know
        $this->requireAdmin();

        if ($reservation->status !== 'pending') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Only pending reservations can be rejected.'], 409);
            }

            return back()->with('error', 'Only pending reservations can be rejected.');
        }

        $reservation->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'admin_remarks' => $request->input('remarks', 'Rejected by admin.'),
        ]);

        $auditLog = AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => $reservation->id,
            'action' => 'rejected',
            'details' => $request->input('remarks', 'Reservation rejected by admin'),
        ]);

        $reservationNotifications->notifyBorrower($reservation);

        return $this->reservationActionResponse($request, 'Reservation rejected successfully.', $auditLog);
    }

    public function destroyReservation(Request $request, Reservation $reservation)
    {
        $this->requireAdmin();

        $reservation->delete();

        $auditLog = AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => $reservation->id,
            'action' => 'deleted',
            'details' => 'Reservation deleted by admin',
        ]);

        return $this->reservationActionResponse($request, 'Reservation deleted successfully.', $auditLog);
    }

    public function updateUserRole(Request $request, \App\Models\User $user)
    {
        // Only the head admin can change user roles, including admin promotion/demotion.
        $this->requireSuperAdmin();

        if ($user->isSuperAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Cannot change head admin role.'], 403);
            }

            return redirect()->route('admin.dashboard', [
                'search' => $request->input('return_search'),
                'role' => $request->input('return_role'),
            ])
                ->with('error', 'Cannot change head admin role.');
        }

        $request->validate([
            'role' => 'required|in:user,admin',
        ]);

        $user->update([
            'is_admin' => $request->input('role') === 'admin',
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => null,
            'action' => 'role_changed',
            'details' => sprintf('Changed role for user %s to %s', $user->email, $request->input('role')),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'User role updated successfully.']);
        }

        return redirect()->route('admin.dashboard', [
            'search' => $request->input('return_search'),
            'role' => $request->input('return_role'),
        ])
            ->with('status', 'User role updated successfully.');
    }

    public function destroyUser(Request $request, \App\Models\User $user)
    {
        // Delete a user account. Only the head admin may perform this action.
        $this->requireSuperAdmin();

        if ($user->isSuperAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Cannot delete the head admin account.'], 403);
            }

            return redirect()->route('admin.dashboard', [
                'search' => $request->input('return_search'),
                'role' => $request->input('return_role'),
            ])
                ->with('error', 'Cannot delete the head admin account.');
        }

        if ($user->id === Auth::id()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You cannot delete your own account here.'], 403);
            }

            return redirect()->route('admin.dashboard', [
                'search' => $request->input('return_search'),
                'role' => $request->input('return_role'),
            ])
                ->with('error', 'You cannot delete your own account here.');
        }

        $user->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => null,
            'action' => 'user_deleted',
            'details' => sprintf('Deleted user %s', $user->email),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'User deleted successfully.']);
        }

        return redirect()->route('admin.dashboard', [
            'search' => $request->input('return_search'),
            'role' => $request->input('return_role'),
        ])
            ->with('status', 'User deleted successfully.');
    }
}
