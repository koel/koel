<?php

namespace Tests\Feature\Commands;

use App\Console\Commands\ExtractFoldersCommand;
use App\Models\Setting;
use App\Models\Song;
use App\Services\MediaBrowser;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExtractFoldersCommandTest extends TestCase
{
    #[Test]
    public function rejectNonLocalStorage(): void
    {
        config(['koel.storage_driver' => 's3']);

        $this->artisan('koel:extract-folders')->assertExitCode(ExtractFoldersCommand::INVALID);
    }

    #[Test]
    public function failWhenMediaPathIsNotSet(): void
    {
        config(['koel.storage_driver' => 'local']);
        Setting::set('media_path', '');

        $this->artisan('koel:extract-folders')->assertExitCode(2);
    }

    #[Test]
    public function extractFoldersFromSongPaths(): void
    {
        config(['koel.storage_driver' => 'local']);
        Setting::set('media_path', '/tmp/media');

        $song = Song::factory()->createOne(['path' => '/tmp/media/Rock/song.mp3', 'folder_id' => null]);

        $browser = Mockery::mock(MediaBrowser::class);
        $browser
            ->shouldReceive('maybeCreateFolderStructureForSong')
            ->once()
            ->with(Mockery::on(static fn (Song $s) => $s->id === $song->id));

        $this->app->instance(MediaBrowser::class, $browser);

        $this->artisan('koel:extract-folders')->assertSuccessful();
    }
}
