<?php

namespace App\Models;

use App\Events\LibraryChanged;
use Illuminate\Database\Eloquent\Model;
use Lastfm;

/**
 * @property string path
 * @property string title
 * @property Album album
 */
class Song extends Model
{
    protected $guarded = [];

    /**
     * Attributes to be hidden from JSON outputs.
     * Here we specify to hide lyrics as well to save some bandwidth (actually, lots of it).
     * Lyrics can then be queried on demand.
     *
     * @var array
     */
    protected $hidden = ['lyrics', 'created_at', 'updated_at', 'path', 'mtime'];

    /**
     * @var array
     */
    protected $casts = [
        'length' => 'float',
        'mtime' => 'int',
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    public function playlists()
    {
        return $this->belongsToMany(Playlist::class);
    }

    /**
     * Scrobble the song using Last.fm service.
     *
     * @param string $timestamp The UNIX timestamp in which the song started playing.
     *
     * @return mixed
     */
    public function scrobble($timestamp)
    {
        // Don't scrobble the unknown guys. No one knows them.
        if ($this->album->artist->isUnknown()) {
            return false;
        }

        // If the current user hasn't connected to Last.fm, don't do shit.
        if (!$sessionKey = auth()->user()->getLastfmSessionKey()) {
            return false;
        }

        return Lastfm::scrobble(
            $this->album->artist->name,
            $this->title,
            $timestamp,
            $this->album->name === Album::UNKNOWN_NAME ? '' : $this->album->name,
            $sessionKey
        );
    }

    /**
     * Get a Song record using its path.
     *
     * @param string $path
     *
     * @return Song|null
     */
    public static function byPath($path)
    {
        return self::find(File::getHash($path));
    }

    /**
     * Update song info.
     *
     * @param array $ids
     * @param array $data The data array, with these supported fields:
     *                    - title
     *                    - artistName
     *                    - albumName
     *                    - lyrics
     *                    All of these are optional, in which case the info will not be changed
     *                    (except for lyrics, which will be emptied).
     *
     * @return
     */
    public static function updateInfo($ids, $data)
    {
        /*
         * The artist that our songs will be associated to.
         * If they are not existing yet, we will create the object.
         *
         * @var Artist
         */
        $targetArtist = null;

        /*
         * The album that our songs will be associated to.
         * If it can't be found, we'll create it.
         *
         * @var Album
         */
        $targetAlbum = null;

        /*
         * An array of the updated songs.
         *
         * @var array
         */
        $updatedSongs = [];

        foreach ((array) $ids as $id) {
            if (!$song = self::with('album', 'album.artist')->find($id)) {
                continue;
            }

            // If we're updating only one song, take into account the title and lyrics
            // and track number.
            if (count($ids) === 1) {
                $song->title = trim($data['title']) ?: $song->title;
                $song->lyrics = trim($data['lyrics']);
                $song->track = trim($data['track']);
            }

            // If "newArtist" is provided, we'll see if such an artist name is found in our database.
            // If negative, we create a new record into $targetArtist.
            if ($artistName = trim($data['artistName'])) {
                $targetArtist = Artist::get($artistName);
            } else {
                $targetArtist = $song->album->artist;
            }

            // Here it gets a little tricky.

            // If "newAlbum" is provided, we find the album OF THE ARTIST.
            // If none is found, create it as $targetAlbum, which is also populated just once.
            if ($albumName = trim($data['albumName'])) {
                $targetAlbum = Album::get($targetArtist, $albumName);

                $song->album_id = $targetAlbum->id;
            } else {
                // The albumName is empty.
                // However, if the artist has changed, it's not the same album anymore.
                // Instead, the song now belongs to another album WITH THE SAME NAME, but under the new artist.
                //
                // See? I told you, it's tricky.
                // Or maybe it's not.
                // Whatever.
                if ($targetArtist->id !== $song->album->artist->id) {
                    $song->album_id = Album::get($targetArtist, $song->album->name)->id;
                }
            }

            $song->save();

            // Get the updated record, with album and all.
            $updatedSong = self::with('album', 'album.artist')->find($id);
            // Make sure lyrics is included in the returned JSON.
            $updatedSong->makeVisible('lyrics');

            $updatedSongs[] = $updatedSong;
        }

        // Our library may have been changed. Broadcast an event to tidy it up if need be.
        if ($updatedSongs) {
            event(new LibraryChanged());
        }

        return $updatedSongs;
    }

    /**
     * Scope a query to only include songs in a given directory.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $path  Full path of the directory
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInDirectory($query, $path)
    {
        // Make sure the path ends with a directory separator.
        $path = rtrim(trim($path), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return $query->where('path', 'LIKE', "$path%");
    }

    /**
     * Sometimes the tags extracted from getID3 are HTML entity encoded.
     * This makes sure they are always sane.
     *
     * @param $value
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = html_entity_decode($value);
    }

    /**
     * Some songs don't have a title.
     * Fall back to the file name (without extension) for such.
     *
     * @param  $value
     *
     * @return string
     */
    public function getTitleAttribute($value)
    {
        return $value ?: pathinfo($this->path, PATHINFO_FILENAME);
    }

    /**
     * Prepare the lyrics for displaying.
     *
     * @param $value
     *
     * @return string
     */
    public function getLyricsAttribute($value)
    {
        // We don't use nl2br() here, because the function actually preserve linebreaks -
        // it just _appends_ a "<br />" after each of them. This would case our client
        // implementation of br2nl fails with duplicated linebreaks.
        return str_replace(["\r\n", "\r", "\n"], '<br />', $value);
    }
}
