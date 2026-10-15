<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PropertyReturnNotConfirmed extends Notification
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
            'title' => 'Property items not returned',
            'message' => "{$this->reservation->user->name} reported that {$items} from {$this->reservation->facility->name} have not been returned by the scheduled return time.",
            'action_url' => route('admin.dashboard'),
            'status' => $this->reservation->status,
        ];
    }
}
