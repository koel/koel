<?php

use App\Models\Album;
use App\Models\Song;
use App\Services\Media;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class MediaTest extends TestCase
{
    use DatabaseTransactions, WithoutMiddleware;

    public function testSync()
    {
        $media = new Media();
        $media->sync($this->mediaPath);

        // Standard mp3 files under root path should be recognized
        $this->seeInDatabase('songs', ['path' => $this->mediaPath.'/full.mp3']);

        // Ogg files and audio files in subdirectories should be recognized
        $this->seeInDatabase('songs', ['path' => $this->mediaPath.'/subdir/back-in-black.ogg']);

        // Non-audio files shouldn't be recognized
        $this->notSeeInDatabase('songs', ['path' => $this->mediaPath.'/rubbish.log']);

        // Broken/corrupted audio files shouldn't be recognized
        $this->notSeeInDatabase('songs', ['path' => $this->mediaPath.'/fake.mp3']);

        // Artists should be created
        $this->seeInDatabase('artists', ['name' => 'Cuckoo']);
        $this->seeInDatabase('artists', ['name' => 'Koel']);

        // Albums should be created
        $this->seeInDatabase('albums', ['name' => 'Koel Testing Vol. 1']);

        // Albums and artists should be correctly linked
        $album = Album::whereName('Koel Testing Vol. 1')->first();
        $this->assertEquals('Koel', $album->artist->name);

        $currentCover = $album->cover;

        $song = Song::orderBy('id', 'desc')->first();

        // Modified file should be recognized
        touch($song->path, $time = time());
        $media->sync($this->mediaPath);
        $song = Song::find($song->id);
        $this->assertEquals($time, $song->mtime);

        // Albums with a non-default cover should have their covers overwritten
        $this->assertEquals($currentCover, Album::find($album->id)->cover);
    }
}
