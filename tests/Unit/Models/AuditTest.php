<?php

namespace Tests\Unit\Models;

use App\Models\Audit;
use App\Models\Song;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditTest extends TestCase
{
    #[Test]
    public function pruneAuditsOlderThanTheRetentionPeriod(): void
    {
        config(['audit.console' => true, 'audit.retention_days' => 30]);

        $this->travelTo(now()->subDays(31));
        $oldSong = Song::factory()->createOne();
        $this->travelBack();

        $this->travelTo(now()->subDays(29));
        $recentSong = Song::factory()->createOne();
        $this->travelBack();

        Artisan::call('model:prune', ['--model' => [Audit::class]]);

        self::assertFalse($oldSong->audits()->exists());
        self::assertTrue($recentSong->audits()->exists());
    }
}
