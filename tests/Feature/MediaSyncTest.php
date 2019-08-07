<?php

namespace Tests\Feature;

use App\Events\LibraryChanged;
use App\Libraries\WatchRecord\InotifyWatchRecord;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Services\FileSynchronizer;
use App\Services\MediaSyncService;
use Exception;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use JamesHeinrich\GetID3\GetID3;

class MediaSyncTest extends TestCase
{
    use WithoutMiddleware;

    /** @var MediaSyncService */
    private $mediaService;

    public function setUp(): void
    {
        parent::setUp();
        $this->mediaService = app(MediaSyncService::class);
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function songs_can_be_synced(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        $this->mediaService->sync($this->mediaPath);

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
        $this->assertNotNull($song->artist_id);
        $this->assertTrue($song->album->is_compilation);
        $this->assertEquals(Artist::VARIOUS_ID, $song->album->artist_id);

        $currentCover = $album->cover;

        $song = Song::orderBy('id', 'desc')->first();

        // Modified file should be recognized
        touch($song->path, $time = time());
        $this->mediaService->sync($this->mediaPath);
        $song = Song::find($song->id);
        $this->assertEquals($time, $song->mtime);

        // Albums with a non-default cover should have their covers overwritten
        $this->assertEquals($currentCover, Album::find($album->id)->cover);
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function songs_can_be_force_synced(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        $this->mediaService->sync($this->mediaPath);

        // Make some modification to the records
        $song = Song::orderBy('id', 'desc')->first();
        $originalTitle = $song->title;
        $originalLyrics = $song->lyrics;

        $song->update([
            'title' => "It's John Cena!",
            'lyrics' => 'Booom Wroooom',
        ]);

        // Resync without forcing
        $this->mediaService->sync($this->mediaPath);

        // Validate that the changes are not lost
        $song = Song::orderBy('id', 'desc')->first();
        $this->assertEquals("It's John Cena!", $song->title);
        $this->assertEquals('Booom Wroooom', $song->lyrics);

        // Resync with force
        $this->mediaService->sync($this->mediaPath, [], true);

        // All is lost.
        $song = Song::orderBy('id', 'desc')->first();
        $this->assertEquals($originalTitle, $song->title);
        $this->assertEquals($originalLyrics, $song->lyrics);
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function songs_can_be_synced_with_selectively_tags(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        $this->mediaService->sync($this->mediaPath);

        // Make some modification to the records
        $song = Song::orderBy('id', 'desc')->first();
        $originalTitle = $song->title;

        $song->update([
            'title' => "It's John Cena!",
            'lyrics' => 'Booom Wroooom',
        ]);

        // Sync only the selective tags
        $this->mediaService->sync($this->mediaPath, ['title'], true);

        // Validate that the specified tags are changed, other remains the same
        $song = Song::orderBy('id', 'desc')->first();
        $this->assertEquals($originalTitle, $song->title);
        $this->assertEquals('Booom Wroooom', $song->lyrics);
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function all_tags_are_catered_for_if_syncing_new_file(): void
    {
        // First we sync the test directory to get the data
        $this->mediaService->sync($this->mediaPath);

        // Now delete the first song.
        $song = Song::orderBy('id')->first();
        $song->delete();

        // Selectively sync only one tag
        $this->mediaService->sync($this->mediaPath, ['track'], true);

        // but we still expect the whole song to be added back with all info
        $addedSong = Song::findOrFail($song->id)->toArray();
        $song = $song->toArray();
        array_forget($addedSong, 'created_at');
        array_forget($song, 'created_at');
        $this->assertEquals($song, $addedSong);
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function added_song_is_synced_when_watching(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        $path = $this->mediaPath.'/blank.mp3';

        $this->mediaService->syncByWatchRecord(new InotifyWatchRecord("CLOSE_WRITE,CLOSE $path"));

        $this->seeInDatabase('songs', ['path' => $path]);
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function deleted_song_is_synced_when_watching(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        $this->createSampleMediaSet();
        $song = Song::orderBy('id', 'desc')->first();

        $this->mediaService->syncByWatchRecord(new InotifyWatchRecord("DELETE {$song->path}"));

        $this->notSeeInDatabase('songs', ['id' => $song->id]);
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function deleted_directory_is_synced_when_watching(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        $this->mediaService->sync($this->mediaPath);

        $this->mediaService->syncByWatchRecord(new InotifyWatchRecord("MOVED_FROM,ISDIR {$this->mediaPath}/subdir"));

        $this->notSeeInDatabase('songs', ['path' => $this->mediaPath.'/subdir/sic.mp3']);
        $this->notSeeInDatabase('songs', ['path' => $this->mediaPath.'/subdir/no-name.mp3']);
        $this->notSeeInDatabase('songs', ['path' => $this->mediaPath.'/subdir/back-in-black.mp3']);
    }

    /** @test */
    public function html_entities_in_tags_are_recognized_and_saved_properly(): void
    {
        $this->mockIocDependency(GetID3::class, [
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

        /** @var FileSynchronizer $fileSynchronizer */
        $fileSynchronizer = app(FileSynchronizer::class);
        $info = $fileSynchronizer->setFile(__DIR__.'/songs/blank.mp3')->getFileInfo();

        self::assertEquals('佐倉綾音 Unknown', $info['artist']);
        self::assertEquals('小岩井こ Random', $info['album']);
        self::assertEquals('水谷広実', $info['title']);
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function hidden_files_can_optionally_be_ignored_when_syncing(): void
    {
        config(['koel.ignore_dot_files' => false]);
        $this->mediaService->sync($this->mediaPath);
        $this->seeInDatabase('albums', ['name' => 'Hidden Album']);

        config(['koel.ignore_dot_files' => true]);
        $this->mediaService->sync($this->mediaPath);
        $this->notSeeInDatabase('albums', ['name' => 'Hidden Album']);
    }
}
