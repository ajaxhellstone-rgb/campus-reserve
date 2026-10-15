<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Notifications\PropertyReturnDue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendPropertyReturnReminders extends Command
{
    protected $signature = 'property-returns:notify-due';

    protected $description = 'Notify borrowers when approved property reservations reach their return time';

    public function handle(): int
    {
        $now = now();
        $sent = 0;

        Reservation::query()
            ->where('status', 'approved')
            ->whereNull('return_reminded_at')
            ->whereHas('items')
            ->where(function ($query) use ($now): void {
                $query->whereDate('reservation_date', '<', $now->toDateString())
                    ->orWhere(function ($query) use ($now): void {
                        $query->whereDate('reservation_date', $now->toDateString())
                            ->whereTime('end_time', '<=', $now->format('H:i:s'));
                    });
            })
            ->orderBy('id')
            ->chunkById(100, function ($reservations) use (&$sent): void {
                foreach ($reservations as $reservation) {
                    DB::transaction(function () use ($reservation, &$sent): void {
                        $dueReservation = Reservation::query()
                            ->with(['user', 'facility', 'items'])
                            ->lockForUpdate()
                            ->find($reservation->id);

                        if (! $dueReservation
                            || $dueReservation->status !== 'approved'
                            || $dueReservation->return_reminded_at
                            || $dueReservation->items->isEmpty()) {
                            return;
                        }

                        $dueReservation->user->notify(new PropertyReturnDue($dueReservation));
                        $dueReservation->forceFill(['return_reminded_at' => now()])->save();
                        $sent++;
                    });
                }
            });

        $this->info("Sent {$sent} property return reminder(s).");

        return self::SUCCESS;
    }
}
