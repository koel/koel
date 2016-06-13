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
     * Upon construction, we'll set the path, hash, and associated Song object (if any).
     *
     * @param string|SplFileInfo $path   Either the file's path, or a SplFileInfo object
     * @param getID3             $getID3 A getID3 object for DI (and better performance)
     */
    public function __construct($path, $getID3 = null)
    {
        $this->splFileInfo = $path instanceof SplFileInfo ? $path : new SplFileInfo($path);
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

        if (isset($info['error']) || !isset($info['playtime_seconds'])) {
            return;
        }

        // Copy the available tags over to comment.
        // This is a helper from getID3, though it doesn't really work well.
        // We'll still prefer getting ID3v2 tags directly later.
        // Read on.
        getid3_lib::CopyTagsToComments($info);

        $track = 0;

        // Apparently track number can be stored with different indices as the following.
        $trackIndices = [
            'tags.id3v2.track_number',
            'comments.track',
            'comments.tracknumber',
            'comments.track_number',
        ];

        for ($i = 0; $i < count($trackIndices) && $track === 0; $i++) {
            $track = array_get($info, $trackIndices[$i], [0])[0];
        }

        $disc = null;
        $discIndices = [
            'tags.id3v2.part_of_a_set',
            'comments.disc',
            'comments.disc_number',
            'comments.part_of_a_set',
        ];

        for ($i = 0; $i < count($discIndices) && $disc === null; $i++) {
            $disc = array_get($info, $discIndices[$i], [null])[0];
        }

        $genre = null;
        $genreIndices = [
            'tags.id3v2.genre',
            'comments.genre',
        ];

        for ($i = 0; $i < count($genreIndices) && $genre === null; $i++) {
            $genre = array_get($info, $genreIndices[$i], [null])[0];
        }

        $year = null;
        $yearIndices = [
            'tags.id3v2.year',
            'comments.year',
            'comments.creation_date',
        ];

        for ($i = 0; $i < count($yearIndices) && $year === null; $i++) {
            $year = array_get($info, $yearIndices[$i], [null])[0];
        }

        $props = [
            'artist' => '',
            'album' => '',
            'part_of_a_compilation' => false,
            'title' => '',
            'length' => $info['playtime_seconds'],
            'track' => (int) $track,
            'disc' => $disc === null ? null : (int) $disc,
            'genre' => $genre,
            'lyrics' => '',
            'year' => $year === null ? null : (int) $year,
            'cover' => array_get($info, 'comments.picture', [null])[0],
            'path' => $this->path,
            'mtime' => $this->mtime,
        ];

        if (!$comments = array_get($info, 'comments_html')) {
            return $props;
        }

        // getID3's 'part_of_a_compilation' value can either be null, ['0'], or ['1']
        // We convert it into a boolean here.
        $props['part_of_a_compilation'] = (bool) array_get($comments, 'part_of_a_compilation', [false])[0];

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

        // Fixes #323, where tag names can be htmlentities()'ed
        $props['artist'] = html_entity_decode(trim($artist));
        $props['album'] = html_entity_decode(trim($album));
        $props['title'] = html_entity_decode(trim($title));
        $props['lyrics'] = html_entity_decode(trim($lyrics));

        return $this->info = $props;
    }

    /**
     * Sync the song with all available media info against the database.
     *
     * @param array $tags  The (selective) tags to sync (if the song exists)
     * @param bool  $force Whether to force syncing, even if the file is unchanged
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

        $artist = null;

        if ($this->isChanged() || $force) {
            // This is a changed file, or the user is forcing updates.
            // In such a case, the user must have specified a list of tags to sync.
            // A sample command could be: ./artisan koel:sync --force --tags=artist,album,lyrics
            // We cater for these tags by removing those not specified.

            // There's a special case with 'album though'.
            // If 'part_of_a_compilation' tag is specified, 'album' must be counted in as well.
            // But if 'album' isn't specified, we don't want to update normal albums.
            // This variable is to keep track of this state.
            $changeCompilationAlbumOnly = false;
            if (in_array('part_of_a_compilation', $tags) && !in_array('album', $tags)) {
                $tags[] = 'album';
                $changeCompilationAlbumOnly = true;
            }

            $info = array_intersect_key($info, array_flip($tags));

            // If the "artist" tag is specified, use it.
            // Otherwise, re-use the existing model value.
            $artist = isset($info['artist']) ? Artist::get($info['artist']) : $this->song->album->artist;

            $isCompilation = (bool) array_get($info, 'part_of_a_compilation');

            // If the "album" tag is specified, use it.
            // Otherwise, re-use the existing model value.
            if (isset($info['album'])) {
                $album = $changeCompilationAlbumOnly
                    ? $this->song->album
                    : Album::get($artist, $info['album'], $info['year'], $isCompilation);
            } else {
                $album = $this->song->album;
            }
        } else {
            // The file is newly added.
            $isCompilation = (bool) array_get($info, 'part_of_a_compilation');
            $artist = Artist::get($info['artist']);
            $album = Album::get($artist, $info['album'], $info['year'], $isCompilation);
        }

        if (!empty($info['cover']) && !$album->has_cover) {
            try {
                $album->generateCover($info['cover']);
            } catch (Exception $e) {
                Log::error($e);
            }
        }

        $info['album_id'] = $album->id;

        // If the song is part of a compilation, make sure we properly set its
        // artist and contributing artist attributes.
        if ($isCompilation) {
            $info['contributing_artist_id'] = $artist->id;
        }

        // Remove these values from the info array, so that we can just use the array as model's input data.
        array_forget($info, ['artist', 'album', 'year', 'cover', 'part_of_a_compilation']);

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
