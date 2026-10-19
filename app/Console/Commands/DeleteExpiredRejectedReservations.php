<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;

class DeleteExpiredRejectedReservations extends Command
{
    protected $signature = 'reservations:delete-expired-rejected';

    protected $description = 'Delete rejected reservations after three full days';

    public function handle(): int
    {
        $deleted = Reservation::query()
            ->where('status', 'rejected')
            ->whereNotNull('rejected_at')
            ->where('rejected_at', '<=', now()->subDays(3))
            ->delete();

        $this->info("Deleted {$deleted} rejected reservation(s) older than three days.");

        return self::SUCCESS;
    }
}
