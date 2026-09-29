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

        $manifest = tempnam(sys_get_temp_dir(), 'koel_test_') . '.json';
        File::put($manifest, json_encode($paths));

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
    public function scanChunkLeavesTheTntSearchIndexToTheParentProcess(): void
    {
        $engine = $this->scanOneFileWithSearchDriver('tntsearch');

        self::assertSame([], $engine->updated);
    }

    #[Test]
    public function scanChunkIndexesAsUsualWithOtherSearchDrivers(): void
    {
        $engine = $this->scanOneFileWithSearchDriver('meilisearch');

        $songId = Song::query()
            ->where('path', realpath($this->mediaPath . '/full.mp3'))
            ->value('id');

        self::assertSame([(string) $songId], $engine->updatedKeysOf(Song::class));
    }

    private function scanOneFileWithSearchDriver(string $driver): RecordingSearchEngine
    {
        $engine = new RecordingSearchEngine();
        $manager = $this->app->make(EngineManager::class);
        // @mago-ignore lint:prefer-static-closure
        $manager->extend($driver, fn () => $engine);
        $manager->forgetDrivers();
        config(['scout.driver' => $driver]);

        $owner = create_admin();
        $manifest = tempnam(sys_get_temp_dir(), 'koel_test_') . '.json';
        File::put($manifest, json_encode([realpath($this->mediaPath . '/full.mp3')]));

        try {
            $this->artisan('koel:scan:chunk', ['manifest' => $manifest, '--owner' => $owner->id])->assertSuccessful();
        } finally {
            File::delete($manifest);
        }

        return $engine;
    }

    #[Test]
    public function scanChunkOutputsJsonLines(): void
    {
        $owner = create_admin();

        $paths = [realpath($this->mediaPath . '/full.mp3')];

        $manifest = tempnam(sys_get_temp_dir(), 'koel_test_') . '.json';
        File::put($manifest, json_encode($paths));

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
    public function scanChunkFailsWithMissingManifest(): void
    {
        $this->artisan('koel:scan:chunk', [
            'manifest' => '/nonexistent/path.json',
            '--owner' => 1,
        ])->assertFailed();
    }
}
