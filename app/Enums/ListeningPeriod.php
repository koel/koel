<?php

namespace App\Enums;

use Illuminate\Support\Carbon;

enum ListeningPeriod: string
{
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';
    case AllTime = 'all';

    public function startsAt(): ?Carbon
    {
        return match ($this) {
            self::Week => now()->subDays(7),
            self::Month => now()->subDays(30),
            self::Year => now()->subYear(),
            self::AllTime => null,
        };
    }
}
