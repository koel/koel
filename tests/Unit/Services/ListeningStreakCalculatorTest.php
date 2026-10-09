<?php

namespace Tests\Unit\Services;

use App\Services\ListeningStreakCalculator;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListeningStreakCalculatorTest extends TestCase
{
    private ListeningStreakCalculator $calculator;

    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-09 15:00:00');
        $this->calculator = new ListeningStreakCalculator();
    }

    #[Test]
    public function countTheCurrentRunOfDaysEndingToday(): void
    {
        $streak = $this->calculator->calculateStreak(
            [
                '2026-10-07T10:00:00Z',
                '2026-10-08T10:00:00Z',
                '2026-10-09T10:00:00Z',
            ],
            'UTC',
        );

        self::assertSame(3, $streak->currentDays);
    }

    #[Test]
    public function keepTheCurrentRunAliveUntilTodayEnds(): void
    {
        $streak = $this->calculator->calculateStreak(
            [
                '2026-10-07T10:00:00Z',
                '2026-10-08T10:00:00Z',
            ],
            'UTC',
        );

        self::assertSame(2, $streak->currentDays);
    }

    #[Test]
    public function endTheCurrentRunAfterAMissedDay(): void
    {
        $streak = $this->calculator->calculateStreak(['2026-10-07T10:00:00Z'], 'UTC');

        self::assertSame(0, $streak->currentDays);
    }

    #[Test]
    public function findTheLongestRun(): void
    {
        $streak = $this->calculator->calculateStreak([
            '2026-09-01T10:00:00Z',
            '2026-09-02T10:00:00Z',
            '2026-09-03T10:00:00Z',
            '2026-09-10T10:00:00Z',
            '2026-10-09T10:00:00Z',
        ], 'UTC');

        self::assertSame(3, $streak->longestDays);
        self::assertSame(1, $streak->currentDays);
    }

    #[Test]
    public function countDaysInTheListenersTimezone(): void
    {
        $streak = $this->calculator->calculateStreak(
            [
                '2026-10-08T23:30:00Z',
                '2026-10-09T10:00:00Z',
            ],
            'Asia/Tokyo',
        );

        self::assertSame(1, $streak->longestDays);
    }
}
