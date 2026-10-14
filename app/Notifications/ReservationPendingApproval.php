<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Reservation;

class ReservationPendingApproval extends Notification
{
    use Queueable;

    // I keep the reservation data so I can include details in the notification
    public Reservation $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $reservation = $this->reservation;
        $userName = optional($reservation->user)->name ?? 'A user';
        $facilityName = optional($reservation->facility)->name ?? 'a facility';
        $date = optional($reservation->reservation_date)?->format('M d, Y') ?? 'a date';
        $start = optional($reservation->start_time)?->format('H:i') ?? '--:--';
        $end = optional($reservation->end_time)?->format('H:i') ?? '--:--';
        $items = $reservation->items
            ->map(fn ($item) => "{$item->name} × {$item->pivot->quantity}")
            ->join(', ');
        $itemDetails = $items !== '' ? " Items: {$items}." : '';
        $timeDetails = $items !== ''
            ? " Use time: {$start}; return time: {$end}."
            : " from {$start} to {$end}.";
        $purposeDetails = $reservation->purpose !== '' ? " Purpose: {$reservation->purpose}." : '';

        return [
            'reservation_id' => $reservation->id,
            'title' => 'Reservation change request',
            'message' => "{$userName} requested {$facilityName} on {$date}.{$timeDetails}{$purposeDetails}{$itemDetails}",
            'action_url' => route('admin.dashboard'),
            'status' => $reservation->status,
        ];
    }
}
