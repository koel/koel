<?php

namespace Tests\Feature\Commands;

use App\Models\Setting;
use App\Models\Song;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
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
