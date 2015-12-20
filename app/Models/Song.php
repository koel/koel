<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lastfm;

/**
 * @property string path
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
        if ($this->album->artist->id === Artist::UNKNOWN_ID) {
            return false;
        }

        auth()->user()->setHidden([]);

        // If the current user hasn't connected to Last.fm, don't do shit.
        if (!$sessionKey = auth()->user()->getPreference('lastfm_session_key')) {
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
        return nl2br($value);
    }
}
