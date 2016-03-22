<?php

namespace App\Models;

use Exception;
use getID3;
use getid3_lib;
use Illuminate\Support\Facades\Log;
use SplFileInfo;

class File
{
    /**
     * A MD5 hash of the file's path.
     * This value is unique, and can be used to query a Song record.
     *
     * @var string
     */
    protected $hash;

    /**
     * The file's last modified time.
     *
     * @var int
     */
    protected $mtime;

    /**
     * The file's path.
     *
     * @var string
     */
    protected $path;

    /**
     * The getID3 object, for ID3 tag reading.
     *
     * @var getID3
     */
    protected $getID3;

    /**
     * The SplFileInfo object of the file.
     *
     * @var SplFileInfo
     */
    protected $splFileInfo;

    /**
     * The song model that's associated with this file.
     *
     * @var Song
     */
    protected $song;

    /**
     * Construct our File object.
     * Upon construction,.
     *
     * @param string|SplFileInfo $path   Either the file's path, or a SplFileInfo object
     * @param getID3             $getID3 A getID3 object for DI (and better performance)
     */
    public function __construct($path, $getID3 = null)
    {
        if ($path instanceof SplFileInfo) {
            $this->splFileInfo = $path;
        } else {
            $this->splFileInfo = new SplFileInfo($path);
        }

        $this->setGetID3($getID3);
        $this->mtime = $this->splFileInfo->getMTime();
        $this->path = $this->splFileInfo->getPathname();
        $this->hash = self::getHash($this->path);
        $this->song = Song::find($this->hash);
    }

    /**
     * Get all applicable ID3 info from the file.
     *
     * @return array|void
     */
    public function getInfo()
    {
        $info = $this->getID3->analyze($this->path);

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
            'path' => $this->path,
            'mtime' => $this->mtime,
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

        return $this->info = $props;
    }

    /**
     * Sync the song with all available media info against the database.
     *
     * @param array $tags  The (selective) tags to sync (if the song exists)
     * @param bool  $force Whether to force syncing, even if the file is unchaged
     *
     * @return bool|Song A Song object on success,
     *                   true if file exists but is unmodified,
     *                   or false on an error.
     */
    public function sync($tags, $force = false)
    {
        // If the file is not new or changed and we're not forcing update, don't do anything.
        if (!$this->isNewOrChanged() && !$force) {
            return true;
        }

        // If the file is invalid, don't do anything.
        if (!$info = $this->getInfo()) {
            return false;
        }

        if ($this->isChanged() || $force) {
            // This is a changed file, or the user is forcing updates.
            // We cater for the tags by removing those not specified.
            $info = array_intersect_key($info, array_flip($tags));

            $artist = isset($info['artist']) ? Artist::get($info['artist']) : $this->song->album->artist;
            $album = isset($info['album']) ? Album::get($artist, $info['album']) : $this->song->album;
        } else {
            $album = Album::get(Artist::get($info['artist']), $info['album']);
        }

        if (!empty($info['cover']) && !$album->has_cover) {
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

        $song = Song::updateOrCreate(['id' => $this->hash], $info);
        $song->save();

        return $song;
    }

    /**
     * Determine if the file is new (its Song record can't be found in the database).
     *
     * @return bool
     */
    public function isNew()
    {
        return !$this->song;
    }

    /**
     * Determine if the file is changed (its Song record is found, but the timestamp is different).
     *
     * @return bool
     */
    public function isChanged()
    {
        return !$this->isNew() && $this->song->mtime !== $this->mtime;
    }

    /**
     * Determine if the file is new or changed.
     *
     * @return bool
     */
    public function isNewOrChanged()
    {
        return $this->isNew() || $this->isChanged();
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

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get a unique hash from a file path.
     *
     * @param string $path
     *
     * @return string
     */
    public static function getHash($path)
    {
        return md5(config('app.key').$path);
    }
}
