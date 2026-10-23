<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationStatusChanged extends Notification
{
    use Queueable;

    // Reservation object used to create the status change notification details.
    public Reservation $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function via($notifiable)
    {
        return $notifiable->email_notifications ? ['database', 'mail'] : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject($data['title'].' - Campus Reserve')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($data['message'])
            ->action('View reservation', $data['action_url'])
            ->line('You can change email notification preferences in your profile settings.');
    }

    public function toArray($notifiable)
    {
        $reservation = $this->reservation;
        $facilityName = $reservation->items->isNotEmpty()
            ? 'property items'
            : (optional($reservation->facility)->name ?? 'a facility');
        $date = optional($reservation->reservation_date)?->format('M d, Y') ?? 'a date';
        $start = optional($reservation->start_time)?->format('H:i') ?? '--:--';
        $end = optional($reservation->end_time)?->format('H:i') ?? '--:--';
        $status = ucfirst($reservation->status);
        $items = $reservation->items
            ->map(fn ($item) => "{$item->name} × {$item->pivot->quantity}")
            ->join(', ');
        $itemDetails = $items !== '' ? " Items: {$items}." : '';
        $timeDetails = $items !== ''
            ? " Use time: {$start}; return time: {$end}."
            : " from {$start} to {$end}.";
        $statusMessage = match ($reservation->status) {
            'pending' => 'is pending and awaiting approval',
            'approved' => 'has been approved',
            'rejected' => 'has been denied',
            default => "has been {$reservation->status}",
        };

        return [
            'reservation_id' => $reservation->id,
            'title' => "Reservation {$status}",
            'message' => "Your reservation for {$facilityName} on {$date}{$timeDetails} {$statusMessage}.{$itemDetails}",
            'action_url' => route('reserve'),
            'status' => $reservation->status,
        ];
    }

    public function toDatabase($notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
