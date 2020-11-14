<?php

namespace Tests\Feature\ObjectStorage;

use App\Events\LibraryChanged;
use App\Models\Song;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\Feature\TestCase;

class S3Test extends TestCase
{
    use WithoutMiddleware;

    public function setUp(): void
    {
        parent::setUp();

        $this->disableMiddlewareForAllTests();
    }

    public function testStoringASong(): void
    {
        $this->post('api/os/s3/song', [
            'bucket' => 'koel',
            'key' => 'sample.mp3',
            'tags' => [
                'title' => 'A Koel Song',
                'album' => 'Koel Testing Vol. 1',
                'artist' => 'Koel',
                'lyrics' => "When you wake up, turn your radio on, and you'll hear this simple song",
                'duration' => 10,
                'track' => 5,
            ],
        ]);

        self::assertDatabaseHas('songs', ['path' => 's3://koel/sample.mp3']);
    }

    public function testRemovingASong(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        Song::factory()->create([
            'path' => 's3://koel/sample.mp3',
        ]);

        $this->delete('api/os/s3/song', [
            'bucket' => 'koel',
            'key' => 'sample.mp3',
        ]);

        self::assertDatabaseMissing('songs', ['path' => 's3://koel/sample.mp3']);
    }
}
