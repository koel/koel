<?php

namespace App\Enums;

use Illuminate\Support\Carbon;

enum ListeningPeriod: string
{
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';
    case ALL_TIME = 'all';

    public function startsAt(): ?Carbon
    {
        return match ($this) {
            self::WEEK => now()->subDays(7),
            self::MONTH => now()->subDays(30),
            self::YEAR => now()->subYear(),
            self::ALL_TIME => null,
        };
    }

    public function previousStartsAt(): ?Carbon
    {
        return match ($this) {
            self::WEEK => now()->subDays(14),
            self::MONTH => now()->subDays(60),
            self::YEAR => now()->subYears(2),
            self::ALL_TIME => null,
        };
    }
}
