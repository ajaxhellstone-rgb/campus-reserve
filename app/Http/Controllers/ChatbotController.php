<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\FacilityItem;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationPendingApproval;
use App\Services\FacilityItemAvailability;
use App\Services\FacilityMaintenanceSchedule;
use App\Services\ReservationNotificationDispatcher;
use App\Services\ReservationTimeWindow;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ChatbotController extends Controller
{
    public function reset(): JsonResponse
    {
        session()->forget([
            'chatbot_reservation',
            'chatbot_cancellation',
            'chatbot_last_action',
            'chatbot_last_reservation_type',
        ]);

        return response()->json(['success' => true]);
    }

    public function respond(
        Request $request,
        FacilityItemAvailability $itemAvailability,
        ReservationNotificationDispatcher $reservationNotifications,
        ReservationTimeWindow $reservationTimeWindow,
        FacilityMaintenanceSchedule $maintenanceSchedule
    ): JsonResponse {
        $message = trim((string) $request->input('message'));
        $normalized = $this->normalizeMessage($message);

        if ($message === '') {
            return response()->json(['reply' => 'Ask me about available rooms, schedules, or reservations.'], 422);
        }

        [$safeMessage, $containsAbuse] = $this->moderateMessage($message);
        if ($containsAbuse) {
            return response()->json([
                'reply' => 'Please keep the conversation respectful. I can help with reservations, room availability, schedules, and your bookings.',
                'user_message' => $safeMessage,
                'moderated' => true,
            ], 422);
        }

        $state = session('chatbot_reservation', []);
        $providedPurpose = false;
        if ($state['awaiting_purpose'] ?? false) {
            if ($this->isNegative($normalized)) {
                session()->forget('chatbot_reservation');
                return response()->json(['reply' => 'Okay, I cancelled that reservation request.']);
            }

            if (mb_strlen($message) > 255) {
                return response()->json(['reply' => 'Please keep the reservation purpose under 255 characters.']);
            }

            $state['purpose'] = $message;
            unset($state['awaiting_purpose']);
            $providedPurpose = true;

            if ($state['confirm_after_purpose'] ?? false) {
                unset($state['confirm_after_purpose']);
                $normalized = 'yes';
            }

            session(['chatbot_reservation' => $state]);
        }

        $facilities = null;
        if (!$providedPurpose && !$this->isSupportedMessage($normalized)) {
            $facilities = Facility::active()->with('items')->orderBy('name')->get();
            $matchesFacility = $facilities->contains(
                fn (Facility $facility) => $this->messageMatchesFacility($facility, $normalized)
            );
            $matchesItem = $facilities->contains(
                fn (Facility $facility) => $this->parseRequestedItems($facility, $normalized, [])->isNotEmpty()
            );

            if (!$matchesFacility && !$matchesItem) {
                return response()->json([
                    'reply' => 'I can help with room and item availability, facility or property reservations, reservation status, schedules, and cancellations. For example, ask “What is my next booking?” or “Is Room 101 free next Friday from 9 to 11 AM?”',
                    'user_message' => $safeMessage,
                ]);
            }
        }

        $cancellation = session('chatbot_cancellation', []);
        if (($cancellation['awaiting_confirmation'] ?? false) && $this->isConfirmation($normalized)) {
            $reservation = Reservation::with(['facility', 'items'])
                ->where('user_id', Auth::id())
                ->find($cancellation['reservation_id'] ?? null);

            if (!$reservation || !$reservation->canBeCancelled()) {
                session()->forget('chatbot_cancellation');
                return response()->json(['reply' => 'That reservation can no longer be cancelled. Please check your current reservations.']);
            }

            $facilityName = $reservation->facility?->name ?? 'the facility';
            $itemSummary = $this->reservationItemSummary($reservation);
            $reservation->delete();
            session()->forget('chatbot_cancellation');

            return response()->json(['reply' => "Your reservation for {$facilityName}{$itemSummary} on " . $reservation->reservation_date->format('F j, Y') . ' was cancelled successfully.']);
        }

        if (($cancellation['awaiting_confirmation'] ?? false) && $this->isNegative($normalized)) {
            session()->forget('chatbot_cancellation');
            return response()->json(['reply' => 'Okay, I did not cancel the reservation.']);
        }

        if (($cancellation['awaiting_selection'] ?? false)) {
            $reservation = Reservation::with(['facility', 'items'])
                ->where('user_id', Auth::id())
                ->whereIn('status', ['pending', 'approved'])
                ->whereDate('reservation_date', '>=', now()->toDateString())
                ->orderBy('reservation_date')
                ->orderBy('start_time')
                ->get()
                ->filter(fn (Reservation $reservation) => $reservation->canBeCancelled())
                ->first(function (Reservation $reservation) use ($normalized) {
                    $facilityName = preg_replace('/[^a-z0-9]/', '', strtolower($reservation->facility?->name ?? ''));
                    $question = preg_replace('/[^a-z0-9]/', '', $normalized);

                    return $facilityName !== '' && str_contains($question, $facilityName);
                });

            if ($reservation) {
                session(['chatbot_cancellation' => [
                    'reservation_id' => $reservation->id,
                    'awaiting_confirmation' => true,
                ]]);

                return response()->json(['reply' => "Do you want to cancel your {$reservation->facility->name} reservation on " . $reservation->reservation_date->format('F j, Y') . '? Reply yes to confirm cancellation.']);
            }
        }

        if (($state['awaiting_confirmation'] ?? false) && $this->isConfirmation($normalized)) {
            if (empty($state['purpose'])) {
                $state['awaiting_purpose'] = true;
                $state['confirm_after_purpose'] = true;
                session(['chatbot_reservation' => $state]);

                return response()->json(['reply' => 'What is the purpose of this reservation?']);
            }

            $facility = Facility::active()->find($state['facility_id'] ?? null);
            $date = $state['date'] ?? null;
            $startTime = $state['start_time'] ?? null;
            $endTime = $state['end_time'] ?? null;

            if (!$facility || !$date || !$startTime || !$endTime) {
                session()->forget('chatbot_reservation');
                return response()->json(['reply' => 'I need the room, date, and time again before I can submit the request.']);
            }

            if (! $reservationTimeWindow->allows($startTime, $endTime)) {
                return response()->json(['reply' => $reservationTimeWindow->validationMessage()]);
            }

            $maintenanceBlock = $maintenanceSchedule->conflictingBlock(
                $facility->id,
                $date,
                $startTime,
                $endTime
            );
            if ($maintenanceBlock) {
                session()->forget('chatbot_reservation');
                return response()->json(['reply' => $maintenanceSchedule->validationMessage($maintenanceBlock)]);
            }

            if (!$facility->isAvailable($date, $startTime, $endTime)) {
                session()->forget('chatbot_reservation');
                return response()->json(['reply' => 'That time is no longer available. Please choose another time or room.']);
            }

            $requestedItems = collect($state['items'] ?? []);
            if (($state['reservation_type'] ?? 'facility') === 'property' && $requestedItems->isEmpty()) {
                session()->put('chatbot_reservation.awaiting_confirmation', false);
                return response()->json(['reply' => 'Select at least one item and quantity before I can submit a property reservation. Tell me the item name and quantity.']);
            }

            $reservation = DB::transaction(function () use ($facility, $date, $startTime, $endTime, $state, $requestedItems, $itemAvailability) {
                if (($state['reservation_type'] ?? 'facility') === 'property') {
                    $itemIds = $requestedItems->pluck('facility_item_id')->map(fn ($id) => (int) $id)->all();
                    $items = FacilityItem::query()
                        ->whereIn('id', $itemIds)
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    if ($items->count() !== count($itemIds) || $requestedItems->isEmpty()) {
                        return response()->json(['reply' => 'I could not verify the selected items. Please start the property reservation again.']);
                    }

                    foreach ($requestedItems as $selection) {
                        $item = $items->get((int) $selection['facility_item_id']);
                        if (!$item || $item->facility_id !== $facility->id) {
                            return response()->json(['reply' => 'A selected item is no longer assigned to this room. Please start the property reservation again.']);
                        }
                    }

                    $availableQuantities = $itemAvailability->availableQuantities($items, $date, $startTime, $endTime);
                    foreach ($requestedItems as $selection) {
                        $item = $items->get((int) $selection['facility_item_id']);
                        $available = $availableQuantities[$item->id] ?? 0;
                        if ((int) $selection['quantity'] > $available) {
                            return response()->json([
                                'reply' => "{$item->name} has only {$available} available for that date and time. Please choose a smaller quantity or another time.",
                            ]);
                        }
                    }
                }

                $reservation = Reservation::create([
                    'user_id' => Auth::id(),
                    'facility_id' => $facility->id,
                    'reservation_date' => $date,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'purpose' => $state['purpose'],
                    'status' => 'pending',
                ]);

                if (($state['reservation_type'] ?? 'facility') === 'property') {
                    $reservation->items()->attach($requestedItems->mapWithKeys(fn ($selection) => [
                        (int) $selection['facility_item_id'] => ['quantity' => (int) $selection['quantity']],
                    ])->all());
                }

                return $reservation;
            });

            if ($reservation instanceof JsonResponse) {
                return $reservation;
            }

            $admins = User::where('is_admin', true)
                ->orWhere('is_super_admin', true)
                ->get();
            $reservation->load(['facility', 'user', 'items']);
            Notification::send($admins, new ReservationPendingApproval($reservation));
            $reservationNotifications->notifyBorrower($reservation);
            session()->forget('chatbot_reservation');
            session([
                'chatbot_last_action' => 'reservation_submitted',
                'chatbot_last_reservation_type' => ($state['reservation_type'] ?? 'facility'),
            ]);

            $requestType = ($state['reservation_type'] ?? 'facility') === 'property' ? 'property reservation' : 'reservation';
            $itemSummary = $this->reservationItemSummary($reservation);

            $timeSummary = ($state['reservation_type'] ?? 'facility') === 'property'
                ? ' with use at ' . Carbon::createFromFormat('H:i', $startTime)->format('g:i A') . ' and return at ' . Carbon::createFromFormat('H:i', $endTime)->format('g:i A')
                : ' from ' . Carbon::createFromFormat('H:i', $startTime)->format('g:i A') . ' to ' . Carbon::createFromFormat('H:i', $endTime)->format('g:i A');

            return response()->json([
                'reply' => "Your {$requestType} request for {$facility->name}{$itemSummary} on " . Carbon::parse($date)->format('F j, Y') . "{$timeSummary} was submitted as a pending request for administrator approval.",
            ]);
        }

        if (session('chatbot_last_action') === 'reservation_submitted') {
            if ($this->isConfirmation($normalized)) {
                $requestType = session('chatbot_last_reservation_type') === 'property' ? 'property' : 'facility';
                return response()->json(['reply' => "Would you like to make another {$requestType} reservation? Tell me the room, date, and time" . ($requestType === 'property' ? ', and the items and quantities' : '') . ', or reply “no” when you are finished.']);
            }

            if ($this->isNegative($normalized) || preg_match('/^(done|finished|that is all|that\'s all)\b/i', $normalized)) {
                $requestType = session('chatbot_last_reservation_type') === 'property' ? 'property reservation' : 'reservation';
                session()->forget(['chatbot_last_action', 'chatbot_last_reservation_type']);
                return response()->json(['reply' => "Okay. Your {$requestType} request is waiting for administrator approval."]);
            }

            session()->forget(['chatbot_last_action', 'chatbot_last_reservation_type']);
        }

        if (preg_match('/\b(new|start over|reset|forget that|different)\b.*\b(reservation|booking|request|room|time)\b/i', $normalized)) {
            session()->forget('chatbot_reservation');
        }

        if (preg_match('/^\s*(hello|hi|hey)\s*[!.?]*\s*$/', $normalized)) {
            return response()->json(['reply' => 'Hi! I can check room and item availability, help reserve facilities or property, and check your reservation status. Try: “Reserve 2 projectors in Room 101 tomorrow from 9 AM to 11 AM.”']);
        }

        if (preg_match('/\b(help|what can you do|what do you do|website|site|account|login|profile)\b/', $normalized)) {
            return response()->json(['reply' => 'I can check facility and item availability, request facility or property reservations, show your reservation status including requested items, and help cancel bookings. For property, include the room, item name and quantity, date, time to use, and time to return.']);
        }

        if ($this->isReservationStatusQuestion($normalized)) {
            $reservationsQuery = Reservation::with(['facility', 'items'])
                ->where('user_id', Auth::id());
            if ($this->isUpcomingReservationQuestion($normalized)) {
                $reservations = $reservationsQuery
                    ->upcoming()
                    ->limit(5)
                    ->get();

                if ($reservations->isEmpty()) {
                    return response()->json(['reply' => 'You do not have any upcoming pending or approved reservations.']);
                }

                $nextReservation = $reservations->first();
                $status = $nextReservation->status === 'approved' ? 'accepted' : 'pending approval';
                $time = $nextReservation->items->isNotEmpty()
                    ? 'use at ' . $nextReservation->start_time->format('g:i A') . ', return at ' . $nextReservation->end_time->format('g:i A')
                    : $nextReservation->start_time->format('g:i A') . ' to ' . $nextReservation->end_time->format('g:i A');

                return response()->json([
                    'reply' => 'Your next reservation is '
                        . ($nextReservation->facility?->name ?? 'an unknown facility')
                        . $this->reservationItemSummary($nextReservation)
                        . ' on ' . $nextReservation->reservation_date->format('F j, Y')
                        . ', ' . $time . " ({$status}).",
                ]);
            }

            $reservations = $reservationsQuery
                ->whereIn('status', ['pending', 'approved', 'rejected'])
                ->orderByDesc('reservation_date')
                ->orderByDesc('start_time')
                ->limit(10)
                ->get();

            if ($reservations->isEmpty()) {
                return response()->json(['reply' => 'You do not have any pending, accepted, or declined reservations.']);
            }

            $summary = $reservations->map(function (Reservation $reservation) {
                $status = match ($reservation->status) {
                    'approved' => 'accepted',
                    'rejected' => 'declined',
                    default => $reservation->status,
                };
                $facility = $reservation->facility?->name ?? 'Unknown facility';
                $date = $reservation->reservation_date->format('F j, Y');
                $time = $reservation->items->isNotEmpty()
                    ? 'use at ' . $reservation->start_time->format('g:i A') . ', return at ' . $reservation->end_time->format('g:i A')
                    : $reservation->start_time->format('g:i A') . ' to ' . $reservation->end_time->format('g:i A');
                $items = $this->reservationItemSummary($reservation);
                $purpose = $reservation->purpose !== '' ? " (purpose: {$reservation->purpose})" : '';

                return "{$facility}{$items}{$purpose} on {$date}, {$time} - {$status}";
            })->join("\n");

            return response()->json(['reply' => "Here are your most recent reservations:\n{$summary}"]);
        }

        if (preg_match('/\b(cancel|remove|delete|get rid of)\b.*\b(reservation|booking|request|it|that)\b/i', $normalized)
            || preg_match('/\b(cancel|remove|delete)\b.*\b(my|the)\b/i', $normalized)) {
            $reservations = Reservation::with(['facility', 'items'])
                ->where('user_id', Auth::id())
                ->whereIn('status', ['pending', 'approved'])
                ->whereDate('reservation_date', '>=', now()->toDateString())
                ->orderBy('reservation_date')
                ->orderBy('start_time')
                ->get()
                ->filter(fn (Reservation $reservation) => $reservation->canBeCancelled());

            $requestedReservation = $reservations->first(function (Reservation $reservation) use ($normalized) {
                $facilityName = preg_replace('/[^a-z0-9]/', '', strtolower($reservation->facility?->name ?? ''));
                $question = preg_replace('/[^a-z0-9]/', '', $normalized);
                $itemNames = $reservation->items->map(fn (FacilityItem $item) => preg_replace('/[^a-z0-9]/', '', strtolower($item->name)));

                return ($facilityName !== '' && str_contains($question, $facilityName))
                    || $itemNames->contains(fn (string $itemName) => $itemName !== '' && str_contains($question, $itemName));
            });

            if ($requestedReservation) {
                $facilityName = $requestedReservation->facility?->name ?? 'the facility';
                $itemSummary = $this->reservationItemSummary($requestedReservation);
                session(['chatbot_cancellation' => [
                    'reservation_id' => $requestedReservation->id,
                    'awaiting_confirmation' => true,
                ]]);

                return response()->json(['reply' => "Do you want to cancel your reservation for {$facilityName}{$itemSummary} on " . $requestedReservation->reservation_date->format('F j, Y') . '? Reply yes to confirm cancellation.']);
            }

            if ($reservations->isEmpty()) {
                return response()->json(['reply' => 'You do not have any pending or accepted reservations that can be cancelled.']);
            }

            $options = $reservations->map(function (Reservation $reservation) {
                return ($reservation->facility?->name ?? 'Unknown facility') . $this->reservationItemSummary($reservation) . ' on ' . $reservation->reservation_date->format('F j, Y');
            })->join('; ');

            session(['chatbot_cancellation' => ['awaiting_selection' => true]]);
            return response()->json(['reply' => "Which reservation do you want to cancel? Your cancellable reservations are: {$options}. Reply with the room name, then I will ask you to confirm."]); 
        }

        $facilities ??= Facility::active()->with('items')->orderBy('name')->get();
        $matchedItemFacility = $facilities->first(
            fn (Facility $facility) => $this->parseRequestedItems($facility, $normalized, [])->isNotEmpty()
        );
        $propertyIntent = (bool) preg_match('/\b(property|properties|item|items|equipment|equipments|inventory|asset|assets|supply|supplies)\b/i', $normalized)
            || ($state['reservation_type'] ?? null) === 'property'
            || $matchedItemFacility !== null;

        $isItemListRequest = $propertyIntent
            && (bool) preg_match('/\b(show|list|what|which|available|have)\b/', $normalized)
            && !(bool) preg_match('/\b(reserve|book|booking)\b/', $normalized)
            && !$this->hasDateOrTimeRequest($normalized);

        if ($isItemListRequest) {
            $requestedFacility = $facilities->first(function (Facility $facility) use ($normalized) {
                return $this->messageMatchesFacility($facility, $normalized);
            }) ?? $matchedItemFacility;

            if ($requestedFacility) {
                $items = $requestedFacility->items
                    ->map(fn (FacilityItem $item) => "{$item->name} ({$item->quantity} in stock)")
                    ->join(', ');
                $inventory = $items !== '' ? $items : 'no items are listed yet';

                return response()->json([
                    'reply' => "{$requestedFacility->name} has {$inventory}. Tell me which item and quantity, plus the date and time, if you would like to reserve property.",
                ]);
            }

            $inventory = $facilities->flatMap(fn (Facility $facility) => $facility->items->map(
                fn (FacilityItem $item) => "{$item->name} in {$facility->name} ({$item->quantity} in stock)"
            ));

            if ($inventory->isEmpty()) {
                return response()->json(['reply' => 'There are no facility items listed for reservation yet.']);
            }

            return response()->json([
                'reply' => 'Available facility items include: ' . $inventory->join(', ') . '. Name the room, item, quantity, date, and time to request a property reservation.',
            ]);
        }

        $isFacilityListRequest = (
            preg_match('/\b(show|list|what|which|name)\b.*\b(rooms?|facilit(?:y|ies)|spaces?)\b/', $normalized)
            && preg_match('/\b(available|active|have|offer|there)\b/', $normalized)
        ) || preg_match('/\b(show|list|what) (rooms?|facilit(?:y|ies)|spaces?)\b/', $normalized);

        if ($isFacilityListRequest && !$this->hasDateOrTimeRequest($normalized)) {
            if ($facilities->isEmpty()) {
                return response()->json(['reply' => 'There are no active facilities available right now.']);
            }

            $rooms = $facilities->map(fn (Facility $facility) => $facility->name)->join(', ');
            return response()->json(['reply' => "Our active facilities are: {$rooms}. Ask me for a date and time to check availability."]); 
        }

        $date = $this->parseDate($normalized);
        [$startTime, $endTime] = $this->parseTimeRange($normalized);
        $requestedFacility = $facilities->first(fn (Facility $facility) => $this->messageMatchesFacility($facility, $normalized));
        $requestedFacility ??= $matchedItemFacility;

            $askingForAlternatives = (bool) preg_match('/\b(other|another|different|all)\s+(rooms?|facilit(?:y|ies))\b/i', $normalized);
            if ($askingForAlternatives) {
                $requestedFacility = null;
                $state['facility_id'] = null;
                $state['awaiting_confirmation'] = false;
            }

        $date ??= !empty($state['date']) ? Carbon::parse($state['date']) : null;
        $startTime ??= $state['start_time'] ?? null;
        $endTime ??= $state['end_time'] ?? null;
        $requestedFacility ??= $facilities->firstWhere('id', $state['facility_id'] ?? null);
        $previousFacilityId = $state['facility_id'] ?? null;
        $previousItems = $previousFacilityId && $requestedFacility && (int) $previousFacilityId !== $requestedFacility->id
            ? []
            : ($state['items'] ?? []);
        $requestedItems = $requestedFacility
            ? $this->parseRequestedItems($requestedFacility, $normalized, $previousItems)
            : collect();
        $isReservationRequest = (bool) preg_match('/\b(reserve|book|booking)\b/i', $normalized)
            || (bool) ($state['reservation_intent'] ?? false);

        $state = [
            'date' => $date?->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'facility_id' => $requestedFacility?->id,
            'reservation_type' => $propertyIntent ? 'property' : 'facility',
            'items' => $requestedItems->values()->all(),
            'purpose' => $state['purpose'] ?? null,
            'reservation_intent' => $isReservationRequest,
        ];
        session(['chatbot_reservation' => $state]);

        if ($isReservationRequest && !$requestedFacility) {
            return response()->json(['reply' => 'Which room or facility would you like to reserve?']);
        }

        if ($isReservationRequest && $requestedFacility && empty($state['purpose'])) {
            $state['awaiting_purpose'] = true;
            session(['chatbot_reservation' => $state]);

            return response()->json(['reply' => "What is the purpose of your reservation for {$requestedFacility->name}?"]);
        }

        if (!$date) {
            return response()->json(['reply' => $propertyIntent
                ? 'What date should I check for the property reservation? You can say “today”, “tomorrow”, or use YYYY-MM-DD.'
                : 'What date should I check? You can say “today”, “tomorrow”, or use YYYY-MM-DD.']);
        }

        if (!$startTime || !$endTime) {
            if ($this->hasTimeRangeInput($normalized)) {
                return response()->json(['reply' => 'Please provide a valid time range where the end time is later than the start time, such as “9 to 10 AM” or “8 AM to 7 PM”.']);
            }

            session(['chatbot_reservation' => $state]);
            return response()->json(['reply' => $propertyIntent
            ? 'What time will you use the items, and what time will you return them? For example: “use from 9 AM and return at 11 AM”.'
                : 'What time range should I check? For example: “from 9 AM to 11 AM”.']);
        }

        $matches = $requestedFacility ? collect([$requestedFacility]) : $facilities;
        $unavailableFacilityIds = Reservation::query()
            ->whereIn('facility_id', $matches->pluck('id')->all())
            ->whereDate('reservation_date', $date->toDateString())
            ->where('status', 'approved')
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->distinct()
            ->pluck('facility_id');
        $available = $matches->reject(fn (Facility $facility) => $unavailableFacilityIds->contains($facility->id));
        $dateLabel = $date->format('F j, Y');
        $timeLabel = Carbon::createFromFormat('H:i', $startTime)->format('g:i A') . ' to ' . Carbon::createFromFormat('H:i', $endTime)->format('g:i A');

        if ($available->isEmpty()) {
            $subject = $requestedFacility ? $requestedFacility->name : 'the requested rooms';
            session()->forget('chatbot_reservation');
            return response()->json(['reply' => "No availability was found for {$subject} on {$dateLabel} from {$timeLabel}. Try another time or date."]); 
        }

        $names = $available->map(fn (Facility $facility) => $facility->name)->join(', ');

        if ($propertyIntent) {
            if (!$requestedFacility) {
                return response()->json([
                    'reply' => "The time is available in {$names}. Which room's items would you like to reserve? You can ask “what items are in {$available->first()->name}?”",
                    'action_url' => route('reserve', ['tab' => 'property']),
                    'action_label' => 'Open property reservations',
                ]);
            }

            if ($requestedItems->isEmpty()) {
                $availableQuantities = $itemAvailability->availableQuantities(
                    $requestedFacility->items,
                    $date->toDateString(),
                    $startTime,
                    $endTime
                );
                $itemAvailabilityList = $requestedFacility->items
                    ->map(fn (FacilityItem $item) => "{$item->name} (" . ($availableQuantities[$item->id] ?? 0) . ' available)')
                    ->join(', ');
                $inventory = $itemAvailabilityList !== '' ? $itemAvailabilityList : 'no items are listed';
                session(['chatbot_reservation' => $state]);

                return response()->json([
                    'reply' => "{$requestedFacility->name} is available on {$dateLabel} from {$timeLabel}, and its items are: {$inventory}. Tell me the item name and quantity to continue.",
                ]);
            }

            $itemAvailabilitySummary = [];
            $availableQuantities = $itemAvailability->availableQuantities(
                $requestedItems->map(fn (array $selection) => $requestedFacility->items->firstWhere('id', (int) $selection['facility_item_id']))->filter(),
                $date->toDateString(),
                $startTime,
                $endTime
            );
            foreach ($requestedItems as $selection) {
                $item = $requestedFacility->items->firstWhere('id', (int) $selection['facility_item_id']);
                if (!$item) {
                    continue;
                }

                $availableQuantity = $availableQuantities[$item->id] ?? 0;
                $itemAvailabilitySummary[] = "{$item->name}: {$availableQuantity} available";

                if ((int) $selection['quantity'] > $availableQuantity) {
                    session(['chatbot_reservation' => $state]);
                    return response()->json([
                        'reply' => "{$item->name} has only {$availableQuantity} available on {$dateLabel} from {$timeLabel}. Tell me a smaller quantity or another date and time.",
                    ]);
                }
            }

            if ($isReservationRequest) {
                session([
                    'chatbot_reservation' => array_merge($state, [
                        'facility_id' => $requestedFacility->id,
                        'awaiting_confirmation' => true,
                    ]),
                ]);

                $requestedItemSummary = $this->formatRequestedItems($requestedFacility, $requestedItems);
                return response()->json([
                    'reply' => "{$requestedFacility->name} and {$requestedItemSummary} are available on {$dateLabel}. The use time is " . Carbon::createFromFormat('H:i', $startTime)->format('g:i A') . ' and the return time is ' . Carbon::createFromFormat('H:i', $endTime)->format('g:i A') . '. Should I submit this property reservation request to the administrators for approval? Reply yes to confirm.',
                ]);
            }

            session([
                'chatbot_reservation' => array_merge($state, [
                    'facility_id' => $requestedFacility->id,
                    'awaiting_confirmation' => true,
                ]),
            ]);

            return response()->json([
                'reply' => "Available on {$dateLabel} at {$requestedFacility->name}: " . implode(', ', $itemAvailabilitySummary) . '. Use time: ' . Carbon::createFromFormat('H:i', $startTime)->format('g:i A') . '; return time: ' . Carbon::createFromFormat('H:i', $endTime)->format('g:i A') . '. Would you like me to submit a property reservation request for those quantities?',
            ]);
        }

        if ($isReservationRequest && $requestedFacility) {
            session([
                'chatbot_reservation' => array_merge($state, [
                    'facility_id' => $requestedFacility->id,
                    'awaiting_confirmation' => true,
                ]),
            ]);

            return response()->json([
                'reply' => "{$requestedFacility->name} is available on {$dateLabel} from {$timeLabel}. Should I submit this reservation request to the administrators for approval? Reply yes to confirm reservation.",
            ]);
        }

        if ($requestedFacility) {
            session([
                'chatbot_reservation' => array_merge($state, [
                    'facility_id' => $requestedFacility->id,
                    'awaiting_confirmation' => true,
                ]),
            ]);

            return response()->json([
                'reply' => "Available on {$dateLabel} from {$timeLabel}: {$requestedFacility->name}. Would you like me to submit this reservation request to the administrators for approval? Reply yes to confirm reservation.",
            ]);
        }

        session([
            'chatbot_reservation' => array_merge($state, [
                'date' => $date->toDateString(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'facility_id' => null,
                'awaiting_confirmation' => false,
            ]),
        ]);
        return response()->json([
            'reply' => ($isReservationRequest ? 'The requested time is available. ' : '') . "Available on {$dateLabel} from {$timeLabel}: {$names}. You can name a room if you want me to submit a reservation request.",
            'action_url' => route('reserve'),
            'action_label' => 'Open reservation form',
        ]);
    }

    private function messageMatchesFacility(Facility $facility, string $message): bool
    {
        $question = preg_replace('/[^a-z0-9]/', '', strtolower($message));
        $facilityName = preg_replace('/[^a-z0-9]/', '', strtolower($facility->name));
        $roomNumber = preg_replace('/[^a-z0-9]/', '', strtolower((string) $facility->room_number));

        return ($facilityName !== '' && str_contains($question, $facilityName))
            || ($roomNumber !== '' && str_contains($question, $roomNumber));
    }

    private function parseRequestedItems(Facility $facility, string $message, array $previousItems): \Illuminate\Support\Collection
    {
        $requestedItems = collect($previousItems)->keyBy(fn (array $item) => (int) $item['facility_item_id']);
        $normalizedMessage = strtolower($message);

        foreach ($facility->items as $item) {
            $itemPattern = str_replace(' ', '\\s+', preg_quote(strtolower($item->name), '/'));
            $itemPattern = '(?<![a-z0-9])' . $itemPattern . 's?(?![a-z0-9])';
            $quantity = null;

            if (preg_match('/\b(\d+)\s*(?:x\s*)?' . $itemPattern . '/i', $normalizedMessage, $matches)) {
                $quantity = (int) $matches[1];
            } elseif (preg_match('/' . $itemPattern . '\s*(?:x|qty|quantity)\s*(\d+)\b/i', $normalizedMessage, $matches)) {
                $quantity = (int) $matches[1];
            } elseif (preg_match('/\b(one|two|three|four|five|six|seven|eight|nine|ten)\s+' . $itemPattern . '/i', $normalizedMessage, $matches)) {
                $quantity = array_search(strtolower($matches[1]), [
                    1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
                    6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten',
                ], true);
            } elseif (preg_match('/' . $itemPattern . '/i', $normalizedMessage)) {
                $quantity = 1;
            }

            if ($quantity !== null && $quantity > 0) {
                $requestedItems->put($item->id, [
                    'facility_item_id' => $item->id,
                    'quantity' => $quantity,
                ]);
            }
        }

        return $requestedItems->values();
    }

    private function reservationItemSummary(Reservation $reservation): string
    {
        if ($reservation->items->isEmpty()) {
            return '';
        }

        $items = $reservation->items
            ->map(fn (FacilityItem $item) => "{$item->name} × {$item->pivot->quantity}")
            ->join(', ');

        return " (items: {$items})";
    }

    private function formatRequestedItems(Facility $facility, \Illuminate\Support\Collection $requestedItems): string
    {
        return $requestedItems
            ->map(function (array $selection) use ($facility) {
                $item = $facility->items->firstWhere('id', (int) $selection['facility_item_id']);

                return $item ? "{$selection['quantity']} × {$item->name}" : null;
            })
            ->filter()
            ->join(', ');
    }

    private function parseDate(string $message): ?Carbon
    {
        if (preg_match('/\b(?:in\s+)?(\d{1,3})\s+days?\b/', $message, $matches)) {
            $days = (int) $matches[1];
            if ($days <= 365) {
                return now()->addDays($days)->startOfDay();
            }
        }

        if (preg_match('/\b(?:(next|this|coming)\s+)?(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/', $message, $matches)) {
            $today = now()->startOfDay();
            $weekday = Carbon::parse($matches[2])->dayOfWeek;
            $daysUntil = ($weekday - $today->dayOfWeek + 7) % 7;

            if (($matches[1] ?? '') === 'next' && $daysUntil === 0) {
                $daysUntil = 7;
            }

            return $today->addDays($daysUntil);
        }

        if (preg_match('/\b(tomorrow|next day)\b/', $message)) {
            return now()->addDay()->startOfDay();
        }

        if (preg_match('/\b(today|this day)\b/', $message)) {
            return now()->startOfDay();
        }

        if (preg_match('/\b(20\d{2}-\d{2}-\d{2})\b/', $message, $matches)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $matches[1])->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        if (preg_match('/\b(January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{1,2}(?:st|nd|rd|th)?(?:,)?(?:\s+20\d{2})?\b/i', $message, $matches)) {
            try {
                $dateText = preg_replace('/(st|nd|rd|th)\b/i', '', $matches[0]);
                $date = Carbon::parse($dateText);
                if (!preg_match('/20\d{2}/', $dateText)) {
                    $date->year(now()->year);
                    if ($date->isBefore(now()->startOfDay())) {
                        $date->addYear();
                    }
                }

                return $date->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function parseTimeRange(string $message): array
    {
        $message = preg_replace('/\b(?:12\s*)?noon\b/i', '12 pm', $message);
        preg_match('/\b(?:from|between)?\s*(\d{1,2})(?::(\d{2}))?\s*(am|pm)?\s*(?:to|until|through|and|-)\s*(\d{1,2})(?::(\d{2}))?\s*(am|pm)?\b/i', $message, $range);

        if ($range) {
            $startPeriod = isset($range[3]) && $range[3] !== '' ? strtolower($range[3]) : null;
            $endPeriod = isset($range[6]) && $range[6] !== '' ? strtolower($range[6]) : $startPeriod;
            $startPeriodWasMissing = $startPeriod === null;
            $endPeriodWasMissing = !isset($range[6]) || $range[6] === '';

            if (!$startPeriod && !$endPeriod) {
                if ((int) $range[1] <= 12 && (int) $range[4] <= 12) {
                    $startPeriod = 'am';
                    $endPeriod = 'am';
                }
            } elseif (!$startPeriod) {
                $startPeriod = $endPeriod;
            }

            $times = [
                $this->formatTime($range[1], $range[2] ?? '', $startPeriod),
                $this->formatTime($range[4], $range[5] ?? '', $endPeriod),
            ];

            if ($times[0] !== null && $times[1] !== null && $times[0] >= $times[1]) {
                if ($startPeriodWasMissing && $endPeriod) {
                    $startPeriod = $endPeriod === 'pm' ? 'am' : 'pm';
                    $times[0] = $this->formatTime($range[1], $range[2] ?? '', $startPeriod);
                } elseif ($endPeriodWasMissing && $startPeriod === 'am') {
                    $endPeriod = 'pm';
                    $times[1] = $this->formatTime($range[4], $range[5] ?? '', $endPeriod);
                }
            }

            if ($times[0] === null || $times[1] === null || $times[0] >= $times[1]) {
                return [null, null];
            }

            return $times;
        }

        preg_match_all('/\b(\d{1,2})(?::(\d{2}))?\s*(am|pm)\b/i', $message, $matches, PREG_SET_ORDER);
        $times = [];

        foreach ($matches as $match) {
            $time = $this->formatTime($match[1], $match[2], strtolower($match[3]));

            if ($time !== null) {
                $times[] = $time;
            }
        }

        if (count($times) < 2 || $times[0] >= $times[1]) {
            return [null, null];
        }

        return [$times[0], $times[1]];
    }

    private function formatTime(string $hourText, string $minuteText, ?string $period): ?string
    {
        $hour = (int) $hourText;
        $minute = $minuteText !== '' ? (int) $minuteText : 0;

        if ($period === 'pm' && $hour < 12) {
            $hour += 12;
        } elseif ($period === 'am' && $hour === 12) {
            $hour = 0;
        }

        if ($hour > 23 || $minute > 59 || ($period !== null && $hourText > 12)) {
            return null;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }

    private function hasTimeRangeInput(string $message): bool
    {
        return (bool) preg_match('/\b\d{1,2}(?::\d{2})?\s*(?:am|pm)?\s*(?:to|until|through|-)\s*\d{1,2}(?::\d{2})?\s*(?:am|pm)?\b/i', $message);
    }

    private function hasDateOrTimeRequest(string $message): bool
    {
        return (bool) preg_match('/\b(today|tomorrow|20\d{2}-\d{2}-\d{2}|January|February|March|April|May|June|July|August|September|October|November|December|\d{1,2}(?::\d{2})?\s*(?:am|pm))\b/i', $message);
    }

    private function isReservationStatusQuestion(string $message): bool
    {
        if (preg_match('/\b(cancel|remove|delete)\b/i', $message)) {
            return false;
        }

        return (bool) preg_match('/\b(reservations?|bookings?|schedule|appointments?)\b/i', $message)
            && (bool) preg_match('/\b(my|mine|do i have|what|show|list|status|when|next|upcoming|pending|approved|accepted|declined|rejected)\b/i', $message);
    }

    private function isUpcomingReservationQuestion(string $message): bool
    {
        return (bool) preg_match('/\b(next|upcoming|soon|future)\b/i', $message)
            && (bool) preg_match('/\b(reservations?|bookings?|schedule|appointments?)\b/i', $message);
    }

    private function isSupportedMessage(string $message): bool
    {
        return (bool) preg_match('/\b(hello|hi|hey|help|what can you do|website|site|account|login|profile|campus|room|rooms|facility|facilities|property|properties|item|items|equipment|equipments|inventory|asset|assets|supply|supplies|available|availability|schedule|reservation|reservations|reserve|reserved|book|booking|bookings|cancel|cancelled|delete|remove|pending|approved|accepted|declined|rejected|status|today|tomorrow|am|pm|noon|yes|yeah|yep|yup|sure|okay|ok|correct|right|confirm|submit|go ahead|do it|no|nope|nah|not now|done|finished|\d{4}-\d{2}-\d{2})\b/i', $message)
            || $this->hasTimeRangeInput($message);
    }

    private function normalizeMessage(string $message): string
    {
        $normalized = strtolower(trim($message));
        $normalized = str_replace(['’', '`'], "'", $normalized);
        $normalized = preg_replace("/\bwhat's\b/", 'what is', $normalized);
        $normalized = preg_replace("/\bthat's\b/", 'that is', $normalized);
        $normalized = preg_replace('/\b(reservation request|booking request)\b/', 'reservation', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized;
    }

    private function isConfirmation(string $message): bool
    {
        return (bool) preg_match('/^(yes|yeah|yep|yup|sure|okay|ok|correct|right|that is right|that works|please do|go ahead|do it|submit|confirm)(?:\b|[!.?])/i', trim($message));
    }

    private function isNegative(string $message): bool
    {
        return (bool) preg_match('/^(no|nope|nah|not now|do not|don\'t|cancel)(?:\b|[!.?])/i', trim($message));
    }

    private function moderateMessage(string $message): array
    {
        $terms = [
            'fuck', 'fucker', 'fucking', 'shit', 'bitch', 'asshole', 'bastard',
            'damn', 'crap', 'dick', 'piss', 'nigger', 'nigga', 'faggot',
            'fag', 'dyke', 'homo', 'tranny', 'chink', 'spic', 'kike', 'gook',
        ];
        $normalized = strtolower($message);
        $normalized = strtr($normalized, [
            '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a',
            '@' => 'a', '$' => 's', '5' => 's', '7' => 't',
        ]);
        $normalized = preg_replace('/[^a-z]/', '', $normalized);
        $normalized = preg_replace('/(.)\1+/', '$1', $normalized);
        $found = false;

        foreach ($terms as $term) {
            if (str_contains($normalized, $term)) {
                $found = true;
                break;
            }
        }

        if (!$found && preg_match('/(?:hate|kill|hurt|destroy|disgusting|dirty|stupid|gross)(?:all|the|those|these)?(?:gay|lesbian|lgbt|trans)\w*|(?:gay|lesbian|lgbt|trans)\w*(?:hate|kill|hurt|destroy|disgusting|dirty|stupid|gross)|(?:gay|lesbian|lgbt|trans)\w*(?:bad|wrong|inferior|unnatural|perverted|disgusting)/', $normalized)) {
            $found = true;
        }

        if ($found) {
            return ['[Censored]', true];
        }

        return [$message, false];
    }
}
