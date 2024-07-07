<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Setting;
use App\Models\Song;
use Illuminate\Http\UploadedFile;
use Tests\PlusTestCase;

use function Tests\create_user;
use function Tests\test_path;

class UploadTest extends PlusTestCase
{
    private UploadedFile $file;

    public function setUp(): void
    {
        parent::setUp();

        Setting::set('media_path', public_path('sandbox/media'));
        $this->file = UploadedFile::fromFile(test_path('songs/full.mp3'), 'song.mp3'); //@phpstan-ignore-line
    }

    public function testUploads(): void
    {
        $user = create_user();

        $this->postAs('api/upload', ['file' => $this->file], $user)->assertSuccessful();
        self::assertDirectoryExists(public_path("sandbox/media/__KOEL_UPLOADS_\${$user->id}__"));

        /** @var Song $song */
        $song = Song::query()->latest()->first();
        self::assertSame($song->owner_id, $user->id);
        self::assertFalse($song->is_public);
    }
}
