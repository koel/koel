<?php

namespace Tests\Feature\ObjectStorage;

use App\Events\LibraryChanged;
use App\Models\Song;
use Exception;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\Feature\TestCase;

class S3Test extends TestCase
{
    use WithoutMiddleware;

    /**
     * @throws Exception
     */
    public function setUp()
    {
        parent::setUp();
        $this->disableMiddlewareForAllTests();
    }

    public function testStoringASong()
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
        ])->seeInDatabase('songs', ['path' => 's3://koel/sample.mp3']);
    }

    /**
     * @throws Exception
     */
    public function testRemovingASong()
    {
        $this->expectsEvents(LibraryChanged::class);

        factory(Song::class)->create([
            'path' => 's3://koel/sample.mp3',
        ]);

        $this->delete('api/os/s3/song', [
            'bucket' => 'koel',
            'key' => 'sample.mp3',
        ])->notSeeInDatabase('songs', ['path' => 's3://koel/sample.mp3']);
    }
}
