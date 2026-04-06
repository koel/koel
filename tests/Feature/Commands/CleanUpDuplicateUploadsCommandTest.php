<?php

namespace Tests\Feature\Commands;

use App\Models\DuplicateUpload;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class CleanUpDuplicateUploadsCommandTest extends TestCase
{
    #[Test]
    public function removesStaleEntries(): void
    {
        $user = create_user();

        DuplicateUpload::factory()->for($user)->createOne([
            'created_at' => now()->subDays(10),
        ]);
        DuplicateUpload::factory()->for($user)->createOne([
            'created_at' => now()->subDays(8),
        ]);
        $recent = DuplicateUpload::factory()->for($user)->createOne([
            'created_at' => now()->subDays(3),
        ]);

        $this
            ->artisan('koel:clean-up-duplicate-uploads')
            ->expectsOutputToContain('2 stale duplicate upload(s) removed')
            ->assertSuccessful();

        self::assertSame(1, DuplicateUpload::query()->count());
        $this->assertDatabaseHas('duplicate_uploads', ['id' => $recent->id]);
    }

    #[Test]
    public function reportsNothingWhenNoStaleEntries(): void
    {
        $user = create_user();

        DuplicateUpload::factory()->for($user)->createOne([
            'created_at' => now()->subDays(2),
        ]);

        $this
            ->artisan('koel:clean-up-duplicate-uploads')
            ->expectsOutputToContain('No stale duplicate uploads found')
            ->assertSuccessful();

        self::assertSame(1, DuplicateUpload::query()->count());
    }

    #[Test]
    public function respectsCustomDaysOption(): void
    {
        $user = create_user();

        DuplicateUpload::factory()->for($user)->createOne([
            'created_at' => now()->subDays(4),
        ]);
        DuplicateUpload::factory()->for($user)->createOne([
            'created_at' => now()->subDays(2),
        ]);

        $this
            ->artisan('koel:clean-up-duplicate-uploads --days=3')
            ->expectsOutputToContain('1 stale duplicate upload(s) removed')
            ->assertSuccessful();

        self::assertSame(1, DuplicateUpload::query()->count());
    }

    #[Test]
    public function rejectsInvalidDaysOption(): void
    {
        $this
            ->artisan('koel:clean-up-duplicate-uploads --days=0')
            ->expectsOutputToContain('must be a positive integer')
            ->assertFailed();

        $this
            ->artisan('koel:clean-up-duplicate-uploads --days=abc')
            ->expectsOutputToContain('must be a positive integer')
            ->assertFailed();
    }
}
