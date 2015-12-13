<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Setting;
use App\Models\Song;
use getID3;
use getid3_lib;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Finder\Finder;
use App\Console\Commands\SyncMedia;

class Media
{
    /**
     * @var getID3
     */
    protected $getID3;

    protected $guarded = [];

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
        set_time_limit(env('APP_MAX_SCAN_TIME', 600));

        $path = $path ?: Setting::get('media_path');

        $results = [
            'good' => [], // Updated or added files
            'bad' => [], // Bad files
            'ugly' => [], // Unmodified files
        ];

        // For now we only care about mp3 and ogg files.
        // Support for other formats (AAC?) may be added in the future.
        $files = Finder::create()->files()->name('/\.(mp3|ogg)$/')->in($path);

        foreach ($files as $file) {
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

        // Empty albums and artists should be gone as well.
        $inUseAlbums = Song::select('album_id')->groupBy('album_id')->get()->lists('album_id');
        $inUseAlbums[] = Album::UNKNOWN_ID;
        Album::whereNotIn('id', $inUseAlbums)->delete();

        $inUseArtists = Album::select('artist_id')->groupBy('artist_id')->get()->lists('artist_id');
        $inUseArtists[] = Artist::UNKNOWN_ID;
        Artist::whereNotIn('id', $inUseArtists)->delete();
    }

    /**
     * Sync a song with all available media info against the database.
     *
     * @param SplFileInfo $file The SplFileInfo instance of the file.
     *
     * @return bool|Song A Song object on success,
     *                   true if file exists but is unmodified,
     *                   or false on an error.
     */
    public function syncFile(SplFileInfo $file)
    {
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

        $props = [
            'artist' => '',
            'album' => '',
            'title' => '',
            'length' => $info['playtime_seconds'],
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
