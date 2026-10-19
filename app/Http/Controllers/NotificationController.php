<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\User;
use App\Notifications\PropertyReturnDue;
use App\Notifications\PropertyReturnNotConfirmed;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class NotificationController extends Controller
{
    public function unreadSummary()
    {
        $summary = auth()->user()->unreadNotifications()
            ->selectRaw('COUNT(*) as count, MAX(created_at) as latest_at')
            ->first();

        return response()->json([
            'count' => (int) $summary->count,
            'latest_at' => $summary->latest_at,
        ]);
    }

    public function index()
    {
        // Get the logged-in user's notifications, showing the newest first, with 15 per page
        $notifications = auth()->user()->notifications()->latest()->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function respondToPropertyReturn(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'notification_id' => ['required', 'uuid'],
            'response' => ['required', 'in:returned,not_returned'],
        ]);

        [$response, $reservation, $alreadyResponded, $adminNotified] = DB::transaction(function () use ($validated, $reservation) {
            $user = auth()->user();
            $notification = $user->notifications()
                ->whereKey($validated['notification_id'])
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $notification->type === PropertyReturnDue::class
                    && (int) ($notification->data['reservation_id'] ?? 0) === $reservation->id,
                403
            );

            if (! empty($notification->data['response'])) {
                return [$notification->data['response'], $reservation, true, false];
            }

            $lockedReservation = Reservation::query()
                ->whereKey($reservation->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $lockedReservation->status === 'approved'
                    && $lockedReservation->items()->exists(),
                422,
                'This property reservation is no longer awaiting a return confirmation.'
            );

            $returnAt = Carbon::parse(
                $lockedReservation->reservation_date->toDateString()
                    .' '.$lockedReservation->end_time->format('H:i:s')
            );
            abort_if(now()->lt($returnAt), 422, 'The scheduled return time has not passed yet.');

            if ($validated['response'] === 'returned') {
                $lockedReservation->update(['status' => 'completed']);
            }

            $notification->forceFill([
                'data' => array_merge($notification->data, [
                    'response' => $validated['response'],
                    'responded_at' => now()->toIso8601String(),
                    'status' => $validated['response'] === 'returned' ? 'completed' : 'approved',
                ]),
                'read_at' => now(),
            ])->save();

            $lockedReservation->load(['user', 'facility', 'items']);
            $adminNotified = false;

            if ($validated['response'] === 'not_returned') {
                $admins = User::query()
                    ->where(fn ($query) => $query->where('is_admin', true)->orWhere('is_super_admin', true))
                    ->get();

                if ($admins->isEmpty()) {
                    logger()->warning('Borrower reported property items not returned, but no admins were available to notify.', [
                        'reservation_id' => $lockedReservation->id,
                    ]);
                } else {
                    Notification::send($admins, new PropertyReturnNotConfirmed($lockedReservation));
                    $adminNotified = true;
                }
            }

            return [$validated['response'], $lockedReservation, false, $adminNotified];
        });

        if ($alreadyResponded) {
            return back()->with('info', 'You already responded to this return reminder.');
        }

        if ($response === 'not_returned') {
            if (! $adminNotified) {
                return back()->with('error', 'Your response was recorded, but no administrators are available to notify. Please contact the campus administrator directly.');
            }

            return back()->with('success', 'Thanks for letting us know. An administrator has been notified.');
        }

        if ($response === 'returned') {
            return back()->with('success', 'Thanks for confirming. The reservation has been marked as completed.');
        }

        return back()->with('info', 'You already responded to this return reminder.');
    }

    public function markAllRead(Request $request)
    {
        // Mark all the user's unread notifications as read
        auth()->user()->unreadNotifications->markAsRead();

        return back();
    }

    public function destroySelected(Request $request)
    {
        // Delete selected notifications
        // If the notification is about a pending reservation, I also delete that reservation
        // This keeps everything in sync
        $request->validate([
            'selected_notifications' => 'array',
            'selected_notifications.*' => 'string',
        ]);

        $selectedIds = $request->input('selected_notifications', []);
        $notifications = auth()->user()->notifications()->whereIn('id', $selectedIds)->get();

        foreach ($notifications as $notification) {
            if ($notification->type === \App\Notifications\ReservationPendingApproval::class
                && ! empty($notification->data['reservation_id'])) {
                $reservation = Reservation::where('id', $notification->data['reservation_id'])
                    ->where('user_id', auth()->id())
                    ->first();

                if ($reservation && in_array($reservation->status, ['pending', 'approved', 'rejected'])) {
                    $reservation->delete();
                }
            }

            $notification->delete();
        }

        return back()->with('success', 'Selected notifications were removed successfully. Pending reservation requests were also cleared.');
    }
}
