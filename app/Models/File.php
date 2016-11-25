<?php

namespace App\Models;

use Cache;
use Exception;
use getID3;
use getid3_lib;
use Illuminate\Support\Facades\Log;
use Media;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

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
     * The last parsing error text, if any.
     *
     * @var string
     */
    protected $syncError;

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

        // Workaround for #344, where getMTime() fails for certain files with Unicode names
        // on Windows.
        // Yes, beloved Windows.
        try {
            $this->mtime = $this->splFileInfo->getMTime();
        } catch (Exception $e) {
            // Not worth logging the error. Just use current stamp for mtime.
            $this->mtime = time();
        }

        $this->path = $this->splFileInfo->getPathname();
        $this->hash = self::getHash($this->path);
        $this->song = Song::find($this->hash);
        $this->syncError = '';
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
            $this->syncError = isset($info['error']) ? $info['error'][0] : 'No playtime found';

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

        for ($i = 0; $i < count($trackIndices) && $track === 0; ++$i) {
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
            'compilation' => false,
            'title' => '',
            'length' => $info['playtime_seconds'],
            'track' => (int) $track,
            'disc' => $disc === null ? null : (int) $disc,
            'genre' => $genre,
            'lyrics' => '',
            'year' => $year === null ? null : ((int) $year > 1900 ?: ((int) $year % 100) + 1900), // Normalize year
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

        if (!$genre = array_get($info, 'tags.id3v2.genre', [null])[0]) {
            $genre = array_get($comments, 'genre', [''])[0];
        }

        if (!$albumArtist = array_get($info, 'tags.id3v2.band', [null])[0]) {
            $albumArtist = array_get($comments, 'band', [''])[0];
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
        $props['title'] = html_entity_decode(trim($title));
        $props['album'] = html_entity_decode(trim($album));
        $props['artist'] = html_entity_decode(trim($artist));
        $props['genre'] = html_entity_decode(trim($genre));
        $props['albumartist'] = html_entity_decode(trim($albumArtist));
        $props['lyrics'] = html_entity_decode(trim($lyrics));

        // A "compilation" property can is determined by:
        // - "part_of_a_compilation" tag (used by iTunes), or
        // - "albumartist" (used by non-retarded applications).
        $props['compilation'] = (bool) (
            array_get($comments, 'part_of_a_compilation', [false])[0] || $props['albumartist']
        );

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

        // Fixes #366. If the file is new, we use all tags by simply setting $force to false.
        if ($this->isNew()) {
            $force = false;
        }

        $artist = null;

        if ($this->isChanged() || $force) {
            // This is a changed file, or the user is forcing updates.
            // In such a case, the user must have specified a list of tags to sync.
            // A sample command could be: ./artisan koel:sync --force --tags=artist,album,lyrics
            // We cater for these tags by removing those not specified.

            // There's a special case with 'album' though.
            // If 'compilation' tag is specified, 'album' must be counted in as well.
            // But if 'album' isn't specified, we don't want to update normal albums.
            // This variable is to keep track of this state.
            $changeCompilationAlbumOnly = false;
            if (in_array('compilation', $tags, true) && !in_array('album', $tags, true)) {
                $tags[] = 'album';
                $changeCompilationAlbumOnly = true;
            }

            $info = array_intersect_key($info, array_flip($tags));

            // If the "artist" tag is specified, use it.
            // Otherwise, re-use the existing model value.
            $artist = isset($info['artist']) ? Artist::get($info['artist']) : $this->song->album->artist;

            $isCompilation = (bool) array_get($info, 'compilation');

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
            $isCompilation = (bool) array_get($info, 'compilation');
            $artist = Artist::get($info['artist']);
            $album = Album::get($artist, $info['album'], $info['year'], $isCompilation);
        }

        if (!$album->has_cover) {
            // If the album has no cover, we try to get the cover image from existing tag data
            if (!empty($info['cover'])) {
                try {
                    $album->generateCover($info['cover']);
                } catch (Exception $e) {
                    Log::error($e);
                }
            }
            // or, if there's a cover image under the same directory, use it.
            elseif ($cover = $this->getCoverFileUnderSameDirectory()) {
                $album->copyCoverFile($cover);
            }
        }

        $info['album_id'] = $album->id;

        // If the song is part of a compilation, make sure we properly set its
        // artist and contributing artist attributes.
        if ($isCompilation) {
            $info['contributing_artist_id'] = $artist->id;
        }

        if (isset($info['genre'])) {
            $info['genre_id'] = Genre::get($info['genre'])->id;
        }
        // Remove these values from the info array, so that we can just use the array as model's input data.
        array_forget($info, ['artist', 'albumartist', 'album', 'year', 'cover', 'compilation', 'genre']);

        return Song::updateOrCreate(['id' => $this->hash], $info);
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
     * Get the last parsing error's text.
     *
     * @return syncError
     */
    public function getSyncError()
    {
        return $this->syncError;
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
     * Issue #380.
     * Some albums have its own cover image under the same directory as cover|folder.jpg/png.
     * We'll check if such a cover file is found, and use it if positive.
     *
     * @throws \InvalidArgumentException
     *
     * @return string|false The cover file's full path, or false if none found
     */
    private function getCoverFileUnderSameDirectory()
    {
        // As directory scanning can be expensive, we cache and reuse the result.
        $cacheKey = md5($this->path.'_cover');

        if (!is_null($cover = Cache::get($cacheKey))) {
            return $cover;
        }

        $matches = array_keys(iterator_to_array(
            Finder::create()
                ->depth(0)
                ->ignoreUnreadableDirs()
                ->files()
                ->followLinks()
                ->name('/(cov|fold)er\.(jpe?g|png)$/i')
                ->in(dirname($this->path))
        ));

        $cover = $matches ? $matches[0] : false;
        // Even if a file is found, make sure it's a real image.
        if ($cover && exif_imagetype($cover) === false) {
            $cover = false;
        }

        Cache::put($cacheKey, $cover, 24 * 60);

        return $cover;
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
