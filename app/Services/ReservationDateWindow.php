<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class ReservationDateWindow
{
    public function maximumDate(): string
    {
        return now()->addYears(2)->toDateString();
    }

    public function rules(): array
    {
        return [
            'required',
            'date',
            'before_or_equal:'.$this->maximumDate(),
        ];
    }

    public function allows(string $date): bool
    {
        return Carbon::parse($date)->startOfDay()->lessThanOrEqualTo(
            Carbon::parse($this->maximumDate())->startOfDay()
        );
    }
}
