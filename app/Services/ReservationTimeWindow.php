<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReservationTimeWindow
{
    private const DEFAULT_START = '00:00';
    private const DEFAULT_END = '23:59';

    public function isAvailable(): bool
    {
        return Schema::hasTable('reservation_settings');
    }

    public function current(): array
    {
        if (! $this->isAvailable()) {
            return [
                'start_time' => self::DEFAULT_START,
                'end_time' => self::DEFAULT_END,
            ];
        }

        $settings = DB::table('reservation_settings')->where('id', 1)->first();

        return [
            'start_time' => $settings->start_time ?? self::DEFAULT_START,
            'end_time' => $settings->end_time ?? self::DEFAULT_END,
        ];
    }

    public function allows(string $startTime, string $endTime): bool
    {
        $settings = $this->current();

        return $startTime >= $settings['start_time']
            && $endTime <= $settings['end_time'];
    }

    public function validationMessage(): string
    {
        $settings = $this->current();

        return 'Reservations must be scheduled between '
            .date('g:i A', strtotime($settings['start_time']))
            .' and '
            .date('g:i A', strtotime($settings['end_time']))
            .'.';
    }

    public function update(string $startTime, string $endTime): void
    {
        if (! $this->isAvailable()) {
            throw new \RuntimeException('Reservation settings are unavailable until the database migration is applied.');
        }

        $settings = DB::table('reservation_settings')->where('id', 1);
        $values = [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'updated_at' => now(),
        ];

        if ($settings->exists()) {
            $settings->update($values);
            return;
        }

        DB::table('reservation_settings')->insert([
            'id' => 1,
            ...$values,
            'created_at' => now(),
        ]);
    }
}
