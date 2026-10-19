<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Services\ReservationNotificationDispatcher;
use Illuminate\Console\Command;

class RejectExpiredReservations extends Command
{
    protected $signature = 'reservations:reject-expired';

    protected $description = 'Reject pending reservations whose reservation date has passed';

    public function handle(ReservationNotificationDispatcher $reservationNotifications): int
    {
        $cutoffDate = now()->toDateString();
        $rejected = 0;

        Reservation::query()
            ->where('status', 'pending')
            ->whereDate('reservation_date', '<', $cutoffDate)
            ->orderBy('id')
            ->chunkById(100, function ($reservations) use ($cutoffDate, $reservationNotifications, &$rejected): void {
                foreach ($reservations as $reservation) {
                    $updated = Reservation::query()
                        ->whereKey($reservation->id)
                        ->where('status', 'pending')
                        ->whereDate('reservation_date', '<', $cutoffDate)
                        ->update([
                            'status' => 'rejected',
                            'admin_remarks' => 'Automatically rejected because the reservation date has passed.',
                            'updated_at' => now(),
                        ]);

                    if ($updated === 0) {
                        continue;
                    }

                    $reservation->refresh();
                    $reservationNotifications->notifyBorrower($reservation);
                    $rejected++;
                }
            });

        $this->info("Rejected {$rejected} expired pending reservation(s).");

        return self::SUCCESS;
    }
}
