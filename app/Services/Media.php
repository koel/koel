<?php

namespace App\Services;

use App\Console\Commands\SyncMedia;
use App\Events\LibraryChanged;
use App\Libraries\WatchRecord\WatchRecordInterface;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Setting;
use App\Models\Song;
use Exception;
use getID3;
use getid3_lib;
use Illuminate\Support\Facades\Log;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class Media
{
    /**
     * @var getID3
     */
    protected $getID3;

    public function __construct()
    {
        $this->setGetID3();
    }

    /**
     * Sync the media. Oh sync the media.
     *
     * @param string|null $path
     * @param SyncMedia   $syncCommand The SyncMedia command object, to log to console if executed by artisan.
     */
    public function sync($path = null, SyncMedia $syncCommand = null)
    {
        if (!app()->runningInConsole()) {
            set_time_limit(env('APP_MAX_SCAN_TIME', 600));
        }

        $path = $path ?: Setting::get('media_path');

        $results = [
            'good' => [], // Updated or added files
            'bad' => [], // Bad files
            'ugly' => [], // Unmodified files
        ];

        foreach ($this->gatherFiles($path) as $file) {
            $song = $this->syncFile($file);

            if ($song === true) {
                $results['ugly'][] = $file;
            } elseif ($song === false) {
                $results['bad'][] = $file;
            } else {
                $results['good'][] = $file;
            }

            if ($syncCommand) {
                $syncCommand->logToConsole($file->getPathname(), $song);
            }
        }

        // Delete non-existing songs.
        $hashes = array_map(function ($f) {
            return $this->getHash($f->getPathname());
        }, array_merge($results['ugly'], $results['good']));

        Song::whereNotIn('id', $hashes)->delete();

        // Trigger LibraryChanged, so that TidyLibrary handler is fired to, erm, tidy our library.
        event(new LibraryChanged());
    }

    /**
     * Gather all applicable files in a given directory.
     *
     * @param string $path The directory's full path
     *
     * @return array An array of SplFileInfo objects
     */
    public function gatherFiles($path)
    {
        return Finder::create()->files()->name('/\.(mp3|ogg|m4a|flac)$/i')->in($path);
    }

    /**
     * Sync a song with all available media info against the database.
     *
     * @param SplFileInfo|string $file The SplFileInfo instance of the file, or the file path.
     *
     * @return bool|Song A Song object on success,
     *                   true if file exists but is unmodified,
     *                   or false on an error.
     */
    public function syncFile($file)
    {
        if (!($file instanceof SplFileInfo)) {
            $file = new SplFileInfo($file);
        }

        if (!$info = $this->getInfo($file)) {
            return false;
        }

        if (!$this->isNewOrChanged($file)) {
            return true;
        }

        $artist = Artist::get($info['artist']);
        $album = Album::get($artist, $info['album']);

        if ($info['cover'] && !$album->has_cover) {
            try {
                $album->generateCover($info['cover']);
            } catch (Exception $e) {
                Log::error($e);
            }
        }

        $info['album_id'] = $album->id;

        unset($info['artist']);
        unset($info['album']);
        unset($info['cover']);

        $song = Song::updateOrCreate(['id' => $this->getHash($file->getPathname())], $info);
        $song->save();

        return $song;
    }

    /**
     * Sync media using a watch record.
     *
     * @param WatchRecordInterface $record      The watch record.
     * @param SyncMedia|null       $syncCommand The SyncMedia command object, to log to console if executed by artisan.
     */
    public function syncByWatchRecord(WatchRecordInterface $record, SyncMedia $syncCommand = null)
    {
        Log::info("New watch record received: '$record'");
        $path = $record->getPath();

        if ($record->isFile()) {
            Log::info("'$path' is a file.");

            // If the file has been deleted...
            if ($record->isDeleted()) {
                // ...and it has a record in our database, remove it.
                if ($song = Song::byPath($path)) {
                    $song->delete();

                    Log::info("$path deleted.");

                    event(new LibraryChanged());
                } else {
                    Log::info("$path doesn't exist in our database--skipping.");
                }
            }
            // Otherwise, it's a new or changed file. Try to sync it in.
            // File format etc. will be handled by the syncFile method.
            elseif ($record->isNewOrModified()) {
                Log::info($this->syncFile($path) instanceof Song ? "Synchronized $path" : "Invalid file $path");
            }

            return;
        }

        // Record is a directory.
        Log::info("'$path' is a directory.");

        if ($record->isDeleted()) {
            // The directory is removed. We remove all songs in it.
            if ($count = Song::inDirectory($path)->delete()) {
                Log::info("Deleted $count song(s) under $path");
                event(new LibraryChanged());
            } else {
                Log::info("$path is empty--no action needed.");
            }
        } elseif ($record->isNewOrModified()) {
            foreach ($this->gatherFiles($path) as $file) {
                $this->syncFile($file);
            }

            Log::info("Synced all song(s) under $path");
        }
    }

    /**
     * Check if a media file is new or changed.
     * A file is considered existing and unchanged only when:
     * - its hash (ID) can be found in the database, and
     * - its last modified time is the same with that of the comparing file.
     *
     * @param SplFileInfo $file
     *
     * @return bool
     */
    protected function isNewOrChanged(SplFileInfo $file)
    {
        return !Song::whereIdAndMtime($this->getHash($file->getPathname()), $file->getMTime())->count();
    }

    /**
     * Get ID3 info from a file.
     *
     * @param SplFileInfo $file
     *
     * @return array|null
     */
    public function getInfo(SplFileInfo $file)
    {
        $info = $this->getID3->analyze($file->getPathname());

        if (isset($info['error'])) {
            return;
        }

        // Copy the available tags over to comment.
        // This is a helper from getID3, though it doesn't really work well.
        // We'll still prefer getting ID3v2 tags directly later.
        // Read on.
        getid3_lib::CopyTagsToComments($info);

        if (!isset($info['playtime_seconds'])) {
            return;
        }

        $track = array_get($info, 'comments.track_number', [0])[0];
        if (preg_match('#(\d+)/#', $track, $matches)) {
            $track = $matches[1];
        } elseif ((int) $track) {
            $track = (int) $track;
        }

        $props = [
            'artist' => '',
            'album' => '',
            'title' => '',
            'length' => $info['playtime_seconds'],
            'track' => $track,
            'lyrics' => '',
            'cover' => array_get($info, 'comments.picture', [null])[0],
            'path' => $file->getPathname(),
            'mtime' => $file->getMTime(),
        ];

        if (!$comments = array_get($info, 'comments_html')) {
            return $props;
        }

        // We prefer id3v2 tags over others.
        if (!$artist = array_get($info, 'tags.id3v2.artist', [null])[0]) {
            $artist = array_get($comments, 'artist', [''])[0];
        }

        if (!$album = array_get($info, 'tags.id3v2.album', [null])[0]) {
            $album = array_get($comments, 'album', [''])[0];
        }

        if (!$title = array_get($info, 'tags.id3v2.title', [null])[0]) {
            $title = array_get($comments, 'title', [''])[0];
        }

        if (!$lyrics = array_get($info, 'tags.id3v2.unsynchronised_lyric', [null])[0]) {
            $lyrics = array_get($comments, 'unsynchronised_lyric', [''])[0];
        }

        $props['artist'] = trim($artist);
        $props['album'] = trim($album);
        $props['title'] = trim($title);
        $props['lyrics'] = trim($lyrics);

        return $props;
    }

    /**
     * Generate a unique hash for a file path.
     *
     * @param $path
     *
     * @return string
     */
    public function getHash($path)
    {
        return md5(config('app.key').$path);
    }

    /**
     * @return getID3
     */
    public function getGetID3()
    {
        return $this->getID3;
    }

    /**
     * @param getID3 $getID3
     */
    public function setGetID3($getID3 = null)
    {
        $this->getID3 = $getID3 ?: new getID3();
    }
}
