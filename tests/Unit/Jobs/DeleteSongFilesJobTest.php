<?php

namespace Tests\Unit\Jobs;

use App\Enums\SongStorageType;
use App\Jobs\DeleteSongFiles;
use App\Services\SongStorages\SongStorage;
use App\Values\SongFileInfo;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteSongFilesJobTest extends TestCase
{
    #[Test]
    public function handle(): void
    {
        $files = collect([
            SongFileInfo::make('path/to/song.mp3', SongStorageType::LOCAL),
            SongFileInfo::make('key.mp3', SongStorageType::S3),
        ]);

        /** @var SongStorage|MockInterface $storage */
        $storage = Mockery::mock(SongStorage::class);

        $storage->shouldReceive('delete')
            ->with('path/to/song.mp3', config('koel.backup_on_delete'))
            ->once();

        $storage->shouldReceive('delete')
            ->with('key.mp3', config('koel.backup_on_delete'))
            ->once();

        $job = new DeleteSongFiles($files);
        $job->handle($storage);
    }
}
