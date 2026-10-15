<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PropertyReturnDue extends Notification
{
    use Queueable;

    public function __construct(public Reservation $reservation) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $items = $this->reservation->items
            ->map(fn ($item) => "{$item->name} × {$item->pivot->quantity}")
            ->join(', ');

        return [
            'reservation_id' => $this->reservation->id,
            'title' => 'Property items are due for return',
            'message' => "Your return time for {$items} from {$this->reservation->facility->name} has passed. Have you returned these items?",
            'action_url' => route('notifications.index'),
            'status' => 'approved',
            'response_required' => true,
        ];
    }
}
