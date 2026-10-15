<?php

namespace Tests\Feature\Commands;

use App\Models\Setting;
use App\Models\Song;
use Illuminate\Support\Facades\File;
use Laravel\Scout\EngineManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\RecordingSearchEngine;
use Tests\TestCase;

use function Tests\create_admin;

class ScanChunkCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Setting::set('media_path', realpath($this->mediaPath));
    }

    #[Test]
    public function scanChunkProcessesFiles(): void
    {
        $owner = create_admin();

        $paths = [
            realpath($this->mediaPath . '/full.mp3'),
            realpath($this->mediaPath . '/subdir/back-in-black.ogg'),
        ];

        $manifest = tempnam(sys_get_temp_dir(), 'koel_test_');
        File::put($manifest, implode(PHP_EOL, $paths) . PHP_EOL);

        try {
            $this->artisan('koel:scan:chunk', [
                'manifest' => $manifest,
                '--owner' => $owner->id,
                '--public' => true,
            ])->assertSuccessful();

            $this->assertDatabaseHas(Song::class, [
                'path' => realpath($this->mediaPath . '/full.mp3'),
                'owner_id' => $owner->id,
            ]);

            $this->assertDatabaseHas(Song::class, [
                'path' => realpath($this->mediaPath . '/subdir/back-in-black.ogg'),
                'owner_id' => $owner->id,
            ]);
        } finally {
            File::delete($manifest);
        }
    }

    #[Test]
    public function scanChunkLeavesSearchIndexingToTheParentProcess(): void
    {
        $engine = new RecordingSearchEngine();
        $manager = $this->app->make(EngineManager::class);
        // @mago-ignore lint:prefer-static-closure
        $manager->extend('meilisearch', fn () => $engine);
        $manager->forgetDrivers();
        config(['scout.driver' => 'meilisearch']);

        $owner = create_admin();
        $manifest = tempnam(sys_get_temp_dir(), 'koel_test_');
        File::put($manifest, realpath($this->mediaPath . '/full.mp3') . PHP_EOL);

        try {
            $this->artisan('koel:scan:chunk', ['manifest' => $manifest, '--owner' => $owner->id])->assertSuccessful();
        } finally {
            File::delete($manifest);
        }

        self::assertSame([], $engine->updated);
    }

    #[Test]
    public function scanChunkOutputsJsonLines(): void
    {
        $owner = create_admin();

        $paths = [realpath($this->mediaPath . '/full.mp3')];

        $manifest = tempnam(sys_get_temp_dir(), 'koel_test_');
        File::put($manifest, implode(PHP_EOL, $paths) . PHP_EOL);

        try {
            $this
                ->artisan('koel:scan:chunk', [
                    'manifest' => $manifest,
                    '--owner' => $owner->id,
                    '--public' => true,
                ])
                ->expectsOutputToContain('"type":"Success"')
                ->assertSuccessful();
        } finally {
            File::delete($manifest);
        }
    }

    #[Test]
    public function scanChunkScansOnlyItsSliceOfTheList(): void
    {
        $owner = create_admin();

        $paths = [
            realpath($this->mediaPath . '/full.mp3'),
            realpath($this->mediaPath . '/subdir/back-in-black.ogg'),
            realpath($this->mediaPath . '/lorem.mp3'),
        ];

        $manifest = tempnam(sys_get_temp_dir(), 'koel_test_');
        File::put($manifest, implode(PHP_EOL, $paths) . PHP_EOL);

        try {
            $this->artisan('koel:scan:chunk', [
                'manifest' => $manifest,
                '--owner' => $owner->id,
                '--offset' => 1,
                '--limit' => 1,
            ])->assertSuccessful();
        } finally {
            File::delete($manifest);
        }

        self::assertSame([$paths[1]], Song::query()->pluck('path')->all());
    }
}
