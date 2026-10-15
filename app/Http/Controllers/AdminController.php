<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Facility;
use App\Models\FacilityItem;
use App\Models\Reservation;
use App\Services\FacilityItemAvailability;
use App\Services\ReservationNotificationDispatcher;
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

        $pendingReservations = Reservation::with(['user', 'facility', 'items'])
            ->where('status', 'pending')
            ->orderBy('reservation_date')
            ->orderBy('start_time')
            ->get();

        $recentReservations = Reservation::with(['user', 'facility'])
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('updated_at', 'desc')
            ->take(20)
            ->get();

        $auditLogs = AuditLog::with(['user', 'reservation'])
            ->latest()
            ->take(25)
            ->get();

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

        return view('admin.dashboard', compact('pendingReservations', 'recentReservations', 'auditLogs', 'users', 'search', 'role'));
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
            'amenities' => ['nullable', 'string', 'max:2000'],
            'requires_approval' => ['sometimes', 'boolean'],
        ]);

        $validated['requires_approval'] = $request->boolean('requires_approval');
        $validated['amenities'] = isset($validated['amenities'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['amenities']))))
            : null;
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
            'amenities' => ['nullable', 'string', 'max:2000'],
            'requires_approval' => ['sometimes', 'boolean'],
        ]);

        $validated['requires_approval'] = $request->boolean('requires_approval');
        $validated['amenities'] = isset($validated['amenities'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['amenities']))))
            : null;

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

        $facilities = Facility::query()->orderBy('building')->orderBy('name')->get();
        $items = FacilityItem::with('facility')->orderBy('name')->get();

        return view('admin.property-management', compact('facilities', 'items'));
    }

    public function storeFacilityItem(Request $request)
    {
        $this->requireAdmin();

        $validated = $request->validate([
            'facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'name' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $item = FacilityItem::create($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => null,
            'action' => 'facility_item_created',
            'details' => "Added {$item->name} (quantity: {$item->quantity}) to {$item->facility->name}.",
        ]);

        return redirect()->route('admin.property-management.index')->with('status', 'Facility item added successfully.');
    }

    public function updateFacilityItem(Request $request, FacilityItem $item)
    {
        $this->requireAdmin();

        $validated = $request->validate([
            'facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'name' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $item->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => null,
            'action' => 'facility_item_updated',
            'details' => "Updated {$item->name} (quantity: {$item->quantity}) for {$item->facility->name}.",
        ]);

        return redirect()->route('admin.property-management.index')->with('status', 'Facility item updated successfully.');
    }

    public function destroyFacilityItem(FacilityItem $item)
    {
        $this->requireAdmin();

        $itemName = $item->name;
        $facilityName = $item->facility->name;
        $item->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => null,
            'action' => 'facility_item_deleted',
            'details' => "Removed {$itemName} from {$facilityName}.",
        ]);

        return redirect()->route('admin.property-management.index')->with('status', 'Facility item removed successfully.');
    }

    public function approve(
        Request $request,
        Reservation $reservation,
        FacilityItemAvailability $itemAvailability,
        ReservationNotificationDispatcher $reservationNotifications
    )
    {
        // Mark the reservation as approved and send the user a notification
        $this->requireAdmin();

        if ($reservation->status !== 'pending') {
            return back()->with('error', 'Only pending reservations can be approved.');
        }

        $approvalError = DB::transaction(function () use ($request, $reservation, $itemAvailability) {
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

            $reservedItems = $reservation->items()->orderBy('facility_items.id')->get();
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
                    if ($lockedItem->facility_id !== $reservation->facility_id) {
                        return 'Cannot approve this reservation because one of its items is no longer assigned to this facility.';
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
            return back()->with('error', $approvalError);
        }

        $reservationNotifications->notifyBorrower($reservation);

        return back()->with('status', 'Reservation approved successfully.');
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
            return back()->with('error', 'Only pending reservations can be rejected.');
        }

        $reservation->update([
            'status' => 'rejected',
            'admin_remarks' => $request->input('remarks', 'Rejected by admin.'),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => $reservation->id,
            'action' => 'rejected',
            'details' => $request->input('remarks', 'Reservation rejected by admin'),
        ]);

        $reservationNotifications->notifyBorrower($reservation);

        return back()->with('status', 'Reservation rejected successfully.');
    }

    public function destroyReservation(Request $request, Reservation $reservation)
    {
        $this->requireAdmin();

        $reservation->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => $reservation->id,
            'action' => 'deleted',
            'details' => 'Reservation deleted by admin',
        ]);

        return back()->with('status', 'Reservation deleted successfully.');
    }

    public function updateUserRole(Request $request, \App\Models\User $user)
    {
        // Only the head admin can change user roles, including admin promotion/demotion.
        $this->requireSuperAdmin();

        if ($user->isSuperAdmin()) {
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
            return redirect()->route('admin.dashboard', [
                'search' => $request->input('return_search'),
                'role' => $request->input('return_role'),
            ])
                ->with('error', 'Cannot delete the head admin account.');
        }

        if ($user->id === Auth::id()) {
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

        return redirect()->route('admin.dashboard', [
            'search' => $request->input('return_search'),
            'role' => $request->input('return_role'),
        ])
            ->with('status', 'User deleted successfully.');
    }
}
