<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Services\ReservationNotificationDispatcher;
use Illuminate\Console\Command;

class CompleteExpiredReservations extends Command
{
    protected $signature = 'reservations:complete-expired';

    protected $description = 'Complete approved reservations whose reservation time has passed';

    public function handle(ReservationNotificationDispatcher $reservationNotifications): int
    {
        $now = now();
        $completed = 0;

        Reservation::query()
            ->where('status', 'approved')
            ->whereDoesntHave('items')
            ->where(function ($query) use ($now): void {
                $query->whereDate('reservation_date', '<', $now->toDateString())
                    ->orWhere(function ($query) use ($now): void {
                        $query->whereDate('reservation_date', $now->toDateString())
                            ->whereTime('end_time', '<=', $now->format('H:i:s'));
                    });
            })
            ->orderBy('id')
            ->chunkById(100, function ($reservations) use ($now, $reservationNotifications, &$completed): void {
                foreach ($reservations as $reservation) {
                    $updated = Reservation::query()
                        ->whereKey($reservation->id)
                        ->where('status', 'approved')
                        ->whereDoesntHave('items')
                        ->where(function ($query) use ($now): void {
                            $query->whereDate('reservation_date', '<', $now->toDateString())
                                ->orWhere(function ($query) use ($now): void {
                                    $query->whereDate('reservation_date', $now->toDateString())
                                        ->whereTime('end_time', '<=', $now->format('H:i:s'));
                                });
                        })
                        ->update([
                            'status' => 'completed',
                            'updated_at' => now(),
                        ]);

                    if ($updated === 0) {
                        continue;
                    }

                    $reservation->refresh();
                    $reservationNotifications->notifyBorrower($reservation);
                    $completed++;
                }
            });

        $this->info("Completed {$completed} expired approved reservation(s).");

        return self::SUCCESS;
    }
}
