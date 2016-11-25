<?php

use App\Events\LibraryChanged;
use App\Libraries\WatchRecord\InotifyWatchRecord;
use App\Models\Album;
use App\Models\Artist;
use App\Models\File;
use App\Models\Song;
use App\Services\Media;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery as m;

class MediaTest extends TestCase
{
    use DatabaseTransactions, WithoutMiddleware;

    public function testSync()
    {
        $this->expectsEvents(LibraryChanged::class);

        $media = new Media();
        $media->sync($this->mediaPath);

        // Standard mp3 files under root path should be recognized
        $this->seeInDatabase('songs', [
            'path' => $this->mediaPath.'/full.mp3',
            // Track # should be recognized
            'track' => 5,
        ]);

        // Ogg files and audio files in subdirectories should be recognized
        $this->seeInDatabase('songs', ['path' => $this->mediaPath.'/subdir/back-in-black.ogg']);

        // GitHub issue #380. folder.png should be copied and used as the cover for files
        // under subdir/
        $song = Song::wherePath($this->mediaPath.'/subdir/back-in-black.ogg')->first();
        $this->assertNotNull($song->album->cover);

        // File search shouldn't be case-sensitive.
        $this->seeInDatabase('songs', ['path' => $this->mediaPath.'/subdir/no-name.mp3']);

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

        // Compilation albums, artists and songs must be recognized
        $song = Song::whereTitle('This song belongs to a compilation')->first();
        $this->assertNotNull($song->contributing_artist_id);
        $this->assertTrue($song->album->is_compilation);
        $this->assertEquals(Artist::VARIOUS_ID, $song->album->artist_id);

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

    public function testForceSync()
    {
        $this->expectsEvents(LibraryChanged::class);

        $media = new Media();
        $media->sync($this->mediaPath);

        // Make some modification to the records
        $song = Song::orderBy('id', 'desc')->first();
        $originalTitle = $song->title;
        $originalLyrics = $song->lyrics;

        $song->update([
            'title' => "It's John Cena!",
            'lyrics' => 'Booom Wroooom',
        ]);

        // Resync without forcing
        $media->sync($this->mediaPath);

        // Validate that the changes are not lost
        $song = Song::orderBy('id', 'desc')->first();
        $this->assertEquals("It's John Cena!", $song->title);
        $this->assertEquals('Booom Wroooom', $song->lyrics);

        // Resync with force
        $media->sync($this->mediaPath, [], true);

        // All is lost.
        $song = Song::orderBy('id', 'desc')->first();
        $this->assertEquals($originalTitle, $song->title);
        $this->assertEquals($originalLyrics, $song->lyrics);
    }

    public function testSyncSelectiveTags()
    {
        $this->expectsEvents(LibraryChanged::class);

        $media = new Media();
        $media->sync($this->mediaPath);

        // Make some modification to the records
        $song = Song::orderBy('id', 'desc')->first();
        $originalTitle = $song->title;

        $song->update([
            'title' => "It's John Cena!",
            'lyrics' => 'Booom Wroooom',
        ]);

        // Sync only the selective tags
        $media->sync($this->mediaPath, ['title'], true);

        // Validate that the specified tags are changed, other remains the same
        $song = Song::orderBy('id', 'desc')->first();
        $this->assertEquals($originalTitle, $song->title);
        $this->assertEquals('Booom Wroooom', $song->lyrics);
    }

    public function testAlwaysSyncAllTagsIfFileIsNew()
    {
        $media = new Media();
        $media->sync($this->mediaPath);
        $song = Song::orderBy('id')->first();
        $song->delete();

        // Selectively sync only one tag,
        // but we still expect the whole song to be added back with all info
        $media->sync($this->mediaPath, ['track'], true);
        $this->seeInDatabase('songs', [
            'id' => $song->id,
            'lyrics' => $song->lyrics,
            'title' => $song->title,
            'track' => $song->track,
        ]);
    }

    public function testWatchSingleFileAdded()
    {
        $path = $this->mediaPath.'/blank.mp3';

        (new Media())->syncByWatchRecord(new InotifyWatchRecord("CLOSE_WRITE,CLOSE $path"));

        $this->seeInDatabase('songs', ['path' => $path]);
    }

    public function testWatchSingleFileDeleted()
    {
        $this->expectsEvents(LibraryChanged::class);

        $this->createSampleMediaSet();
        $song = Song::orderBy('id', 'desc')->first();

        (new Media())->syncByWatchRecord(new InotifyWatchRecord("DELETE {$song->path}"));

        $this->notSeeInDatabase('songs', ['id' => $song->id]);
    }

    public function testWatchDirectoryDeleted()
    {
        $this->expectsEvents(LibraryChanged::class);

        $media = new Media();
        $media->sync($this->mediaPath);

        $media->syncByWatchRecord(new InotifyWatchRecord("MOVED_FROM,ISDIR {$this->mediaPath}/subdir"));

        $this->notSeeInDatabase('songs', ['path' => $this->mediaPath.'/subdir/sic.mp3']);
        $this->notSeeInDatabase('songs', ['path' => $this->mediaPath.'/subdir/no-name.MP3']);
        $this->notSeeInDatabase('songs', ['path' => $this->mediaPath.'/subdir/back-in-black.mp3']);
    }

    public function testHtmlEntitiesInTags()
    {
        $getID3 = m::mock(getID3::class, [
            'analyze' => [
                'tags' => [
                    'id3v2' => [
                        'title' => ['&#27700;&#35895;&#24195;&#23455;'],
                        'album' => ['&#23567;&#23721;&#20117;&#12371; Random'],
                        'artist' => ['&#20304;&#20489;&#32190;&#38899; Unknown'],
                    ],
                ],
                'encoding' => 'UTF-8',
                'playtime_seconds' => 100,
            ],
        ]);

        $info = (new File(__DIR__.'/songs/blank.mp3', $getID3))->getInfo();

        $this->assertEquals('佐倉綾音 Unknown', $info['artist']);
        $this->assertEquals('小岩井こ Random', $info['album']);
        $this->assertEquals('水谷広実', $info['title']);
    }

    public function testDotDirectories()
    {
        config(['koel.ignore_dot_files' => false]);
        $media = new Media();
        $media->sync($this->mediaPath);
        $this->seeInDatabase('albums', ['name' => 'Hidden Album']);

        config(['koel.ignore_dot_files' => true]);
        $media->sync($this->mediaPath);
        $this->notSeeInDatabase('albums', ['name' => 'Hidden Album']);
    }
}
