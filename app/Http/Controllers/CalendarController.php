<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Facility;
use App\Models\FacilityItem;
use App\Models\User;
use App\Notifications\ReservationPendingApproval;
use App\Services\FacilityItemAvailability;
use App\Services\ReservationNotificationDispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class CalendarController extends Controller
{
    // Get all reservations and format them as calendar events
    public function events(Request $request)
    {
        // The shared calendar shows approved facility bookings, while keeping
        // each user's reservation purpose private from other users.
        $facilityName = $request->query('facility');
        $query = Reservation::with('facility')
            ->where('status', 'approved');
        
        if ($facilityName) {
            $query->whereHas('facility', function ($q) use ($facilityName) {
                $q->where('name', $facilityName);
            });
        }
        $reservations = $query->get();
        $events = $reservations->map(function ($reservation) {
            // Format the dates and times so the calendar can understand them
            $dateStr = $reservation->reservation_date->format('Y-m-d');
            // Make sure times are in the right format (hours:minutes:seconds)
            $startTime = $reservation->start_time->format('H:i:00');
            $endTime = $reservation->end_time->format('H:i:00');
            $start = $dateStr . 'T' . $startTime;
            $end = $dateStr . 'T' . $endTime;
            return [
                'id' => $reservation->id,
                'title' => $reservation->facility->name,
                'start' => $start,
                'end' => $end,
                'color' => $this->getReservationColor($reservation->id),
                'extendedProps' => [
                    'purpose' => $reservation->user_id === Auth::id() ? $reservation->purpose : null,
                    'status' => $reservation->status,
                    'room' => $reservation->facility->name,
                    'date' => $dateStr,
                    'is_owner' => $reservation->user_id === Auth::id(),
                ],
            ];
        });
        return response()->json($events);
    }

    // Save a new reservation to the database
    public function store(
        Request $request,
        FacilityItemAvailability $itemAvailability,
        ReservationNotificationDispatcher $reservationNotifications
    )
    {
        // First, I validate that all the required info is provided
        // Then I check if the room is actually available at that time
        $hasItemBookings = $request->has('items');
        $validated = $request->validate([
            'facility' => 'required|string',
            'purpose' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'items' => $hasItemBookings ? ['required', 'array', 'min:1'] : ['sometimes', 'array'],
            'items.*.facility_item_id' => ['required', 'integer', 'distinct', 'exists:facility_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $facilityInput = $validated['facility'];
        $facility = ctype_digit($facilityInput)
            ? Facility::active()->findOrFail((int) $facilityInput)
            : Facility::active()->where('name', $facilityInput)->firstOrFail();

        if (!$facility->isAvailable($validated['date'], $validated['start_time'], $validated['end_time'])) {
            return response()->json(['error' => 'Time slot not available'], 409);
        }

        $reservation = DB::transaction(function () use ($validated, $facility, $hasItemBookings, $itemAvailability) {
            $selectedItems = collect($validated['items'] ?? []);

            if ($hasItemBookings) {
                $itemIds = $selectedItems->pluck('facility_item_id')->map(fn ($id) => (int) $id)->all();
                $lockedItems = FacilityItem::query()
                    ->whereIn('id', $itemIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($lockedItems->count() !== count($itemIds)) {
                    throw ValidationException::withMessages([
                        'items' => 'One or more selected items are no longer available.',
                    ]);
                }

                foreach ($selectedItems as $selection) {
                    $item = $lockedItems->get((int) $selection['facility_item_id']);
                    if ($item->facility_id !== $facility->id) {
                        throw ValidationException::withMessages([
                            'items' => 'Selected items must belong to the facility being reserved.',
                        ]);
                    }

                    $availableQuantity = $itemAvailability->availableQuantity(
                        $item,
                        $validated['date'],
                        $validated['start_time'],
                        $validated['end_time']
                    );

                    if ((int) $selection['quantity'] > $availableQuantity) {
                        return response()->json([
                            'error' => "{$item->name} has only {$availableQuantity} available for that date and time.",
                        ], 409);
                    }
                }
            }

            $reservation = Reservation::create([
                'user_id' => Auth::id(),
                'facility_id' => $facility->id,
                'reservation_date' => $validated['date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'purpose' => $validated['purpose'],
                'status' => 'pending',
            ]);

            if ($hasItemBookings) {
                $reservation->items()->attach($selectedItems->mapWithKeys(fn ($selection) => [
                    (int) $selection['facility_item_id'] => ['quantity' => (int) $selection['quantity']],
                ])->all());
            }

            return $reservation;
        });

        if ($reservation instanceof \Illuminate\Http\JsonResponse) {
            return $reservation;
        }

        $admins = User::where('is_admin', true)
            ->orWhere('is_super_admin', true)
            ->get();

        Notification::send($admins, new ReservationPendingApproval($reservation));
        $reservationNotifications->notifyBorrower($reservation);

        return response()->json(['success' => true, 'reservation' => $reservation]);
    }

    public function itemAvailability(
        Request $request,
        Facility $facility,
        FacilityItemAvailability $itemAvailability
    ) {
        abort_unless($facility->is_active, 404);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $items = $facility->items()
            ->orderBy('name')
            ->get()
            ->map(fn (FacilityItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'stock' => $item->quantity,
                'available' => $itemAvailability->availableQuantity(
                    $item,
                    $validated['date'],
                    $validated['start_time'],
                    $validated['end_time']
                ),
            ]);

        return response()->json(['items' => $items]);
    }

    public function pending(Request $request)
    {
        $pendingReservations = Reservation::with('facility')
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->orderBy('reservation_date')
            ->orderBy('start_time')
            ->get()
            ->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'facility' => $reservation->facility->name,
                    'date' => $reservation->reservation_date->format('M d, Y'),
                    'start_time' => $reservation->start_time->format('H:i'),
                    'end_time' => $reservation->end_time->format('H:i'),
                    'purpose' => $reservation->purpose,
                    'status' => $reservation->status,
                ];
            });

        return response()->json($pendingReservations);
    }

    // Pick a color for each reservation so they look different on the calendar
    private function getReservationColor($reservationId)
    {
        // I have 24 nice colors that all look good together
        $colors = [
            '#FF6B6B',  // Vibrant Red
            '#4ECDC4',  // Turquoise
            '#45B7D1',  // Sky Blue
            '#FFA07A',  // Light Salmon
            '#98D8C8',  // Mint
            '#F7DC6F',  // Golden Yellow
            '#BB8FCE',  // Purple
            '#85C1E2',  // Cornflower Blue
            '#F8B88B',  // Peach
            '#A8E6CF',  // Light Green
            '#FFD3B6',  // Light Orange
            '#FF8B94',  // Rose
            '#A8D8EA',  // Powder Blue
            '#AA96DA',  // Lavender
            '#FCBAD3',  // Pink
            '#B4A7D6',  // Soft Purple
            '#73A580',  // Sage Green
            '#F0A202',  // Deep Gold
            '#E76F51',  // Burnt Orange
            '#2A9D8F',  // Teal
            '#E9C46A',  // Sandy Yellow
            '#F4A261',  // Orange
            '#D62828',  // Deep Red
            '#1D3557',  // Navy Blue
        ];
        
        // I cycle through the colors based on the reservation ID so each one gets a different color
        $colorIndex = (intval($reservationId) % count($colors));
        return $colors[$colorIndex];
    }
}
