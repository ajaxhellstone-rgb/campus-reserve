<?php

namespace App\Services;

use App\Models\Reservation;
use App\Notifications\ReservationStatusChanged;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ReservationNotificationDispatcher
{
    public function notifyBorrower(Reservation $reservation): void
    {
        $reservation->loadMissing(['user', 'facility', 'items']);
        $user = $reservation->user;

        if (! $user) {
            return;
        }

        $notification = new ReservationStatusChanged($reservation);
        Notification::sendNow($user, $notification, ['database']);

        if (! $user->email_notifications) {
            return;
        }

        try {
            Notification::sendNow($user, $notification, ['mail']);
        } catch (Throwable $exception) {
            logger()->error('Reservation email notification could not be sent.', [
                'user_id' => $user->id,
                'reservation_id' => $reservation->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
