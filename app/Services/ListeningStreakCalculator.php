<?php

namespace App\Services;

use App\Values\Statistics\ListeningStreak;
use Illuminate\Support\Carbon;

class ListeningStreakCalculator
{
    /**
     * @param list<string> $playedHours UTC hours with at least one play (ISO 8601)
     */
    public function calculateStreak(array $playedHours, string $timezone): ListeningStreak
    {
        $activeDays = collect($playedHours)
            ->map(static fn (string $hour): string => Carbon::parse($hour)->setTimezone($timezone)->toDateString())
            ->unique()
            ->sort()
            ->values();

        return ListeningStreak::make(
            currentDays: self::countCurrentStreak($activeDays->flip()->all(), Carbon::now($timezone)),
            longestDays: self::countLongestStreak($activeDays->all()),
        );
    }

    /** @param array<string, int> $activeDays local dates (Y-m-d) as keys */
    private static function countCurrentStreak(array $activeDays, Carbon $now): int
    {
        $day = $now->copy()->startOfDay();

        if (!isset($activeDays[$day->toDateString()])) {
            $day->subDay();
        }

        $streak = 0;

        while (isset($activeDays[$day->toDateString()])) {
            ++$streak;
            $day->subDay();
        }

        return $streak;
    }

    /** @param list<string> $sortedActiveDays local dates (Y-m-d), oldest first */
    private static function countLongestStreak(array $sortedActiveDays): int
    {
        $longest = 0;
        $current = 0;
        $previousDay = null;

        foreach ($sortedActiveDays as $day) {
            $current = $previousDay && Carbon::parse($previousDay)->addDay()->toDateString() === $day
                ? $current + 1
                : 1;
            $longest = max($longest, $current);
            $previousDay = $day;
        }

        return $longest;
    }
}
