<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;

class DeleteExpiredCompletedReservations extends Command
{
    protected $signature = 'reservations:delete-expired-completed';

    protected $description = 'Delete completed reservations three days after their scheduled end time';

    public function handle(): int
    {
        $cutoff = now()->subDays(3);

        $deleted = Reservation::query()
            ->where('status', 'completed')
            ->where(function ($query) use ($cutoff): void {
                $query->whereDate('reservation_date', '<', $cutoff->toDateString())
                    ->orWhere(function ($query) use ($cutoff): void {
                        $query->whereDate('reservation_date', $cutoff->toDateString())
                            ->whereTime('end_time', '<=', $cutoff->format('H:i:s'));
                    });
            })
            ->delete();

        $this->info("Deleted {$deleted} completed reservation(s) older than three days.");

        return self::SUCCESS;
    }
}
