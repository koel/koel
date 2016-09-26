<?php

namespace App\Models;

use App\Events\LibraryChanged;
use AWS;
use Aws\AwsClient;
use Cache;
use Illuminate\Database\Eloquent\Model;
use Lastfm;
use YouTube;

/**
 * @property string path
 * @property string title
 * @property Album  album
 * @property int    contributing_artist_id
 * @property Artist contributingArtist
 * @property Artist artist
 * @property string s3_params
 * @property float  length
 * @property string lyrics
 * @property int    track
 * @property int    album_id
 * @property int    id
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
    protected $hidden = ['lyrics', 'updated_at', 'path', 'mtime'];

    /**
     * @var array
     */
    protected $casts = [
        'length' => 'float',
        'mtime' => 'int',
        'track' => 'int',
        'contributing_artist_id' => 'int',
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    public function contributingArtist()
    {
        return $this->belongsTo(ContributingArtist::class);
    }

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
        if ($this->artist->isUnknown()) {
            return false;
        }

        // If the current user hasn't connected to Last.fm, don't do shit.
        if (!$sessionKey = auth()->user()->lastfm_session_key) {
            return false;
        }

        return Lastfm::scrobble(
            $this->artist->name,
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
     * @return array
     */
    public static function updateInfo($ids, $data)
    {
        /*
         * An array of the updated songs.
         *
         * @var array
         */
        $updatedSongs = [];

        $ids = (array) $ids;
        // If we're updating only one song, take into account the title, lyrics, and track number.
        $single = count($ids) === 1;

        foreach ($ids as $id) {
            if (!$song = self::with('album', 'album.artist')->find($id)) {
                continue;
            }

            $updatedSongs[] = $song->updateSingle(
                $single ? trim($data['title']) : $song->title,
                trim($data['albumName'] ?: $song->album->name),
                trim($data['artistName']) ?: $song->artist->name,
                $single ? trim($data['lyrics']) : $song->lyrics,
                $single ? (int) $data['track'] : $song->track,
                (int) $data['compilationState']
            );
        }

        // Our library may have been changed. Broadcast an event to tidy it up if need be.
        if ($updatedSongs) {
            event(new LibraryChanged());
        }

        return $updatedSongs;
    }

    /**
     * Update a single song's info.
     *
     * @param string $title
     * @param string $albumName
     * @param string $artistName
     * @param string $lyrics
     * @param int    $track
     * @param int    $compilationState
     *
     * @return self
     */
    public function updateSingle($title, $albumName, $artistName, $lyrics, $track, $compilationState)
    {
        // If the artist name is "Various Artists", it's a compilation song no matter what.
        if ($artistName === Artist::VARIOUS_NAME) {
            $compilationState = 1;
        }

        // If the compilation state is "no change," we determine it via the current
        // "contributing_artist_id" field value.
        if ($compilationState === 2) {
            $compilationState = $this->contributing_artist_id ? 1 : 0;
        }

        $album = null;

        if ($compilationState === 0) {
            // Not a compilation song
            $this->contributing_artist_id = null;
            $albumArtist = Artist::get($artistName);
            $album = Album::get($albumArtist, $albumName, false);
        } else {
            $contributingArtist = Artist::get($artistName);
            $this->contributing_artist_id = $contributingArtist->id;
            $album = Album::get(Artist::getVarious(), $albumName, true);
        }

        $this->album_id = $album->id;
        $this->title = $title;
        $this->lyrics = $lyrics;
        $this->track = $track;

        $this->save();

        // Get the updated record, with album and all.
        $updatedSong = self::with('album', 'album.artist', 'contributingArtist')->find($this->id);
        // Make sure lyrics is included in the returned JSON.
        $updatedSong->makeVisible('lyrics');

        return $updatedSong;
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
     * Get all songs favored by a user.
     *
     * @param User $user
     * @param bool $toArray
     *
     * @return \Illuminate\Database\Eloquent\Collection|array
     */
    public static function getFavorites(User $user, $toArray = false)
    {
        $songs = Interaction::where([
            'user_id' => $user->id,
            'liked' => true,
        ])
            ->with('song')
            ->get()
            ->pluck('song');

        return $toArray ? $songs->toArray() : $songs;
    }

    /**
     * Get the song's Object Storage url for streaming or downloading.
     *
     * @param AwsClient $s3
     *
     * @return string
     */
    public function getObjectStoragePublicUrl(AwsClient $s3 = null)
    {
        // If we have a cached version, just return it.
        if ($cached = Cache::get("OSUrl/{$this->id}")) {
            return $cached;
        }

        // Otherwise, we query S3 for the presigned request.
        if (!$s3) {
            $s3 = AWS::createClient('s3');
        }

        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $this->s3_params['bucket'],
            'Key' => $this->s3_params['key'],
        ]);

        // Here we specify that the request is valid for 1 hour.
        // We'll also cache the public URL for future reuse.
        $request = $s3->createPresignedRequest($cmd, '+1 hour');
        $url = (string) $request->getUri();
        Cache::put("OSUrl/{$this->id}", $url, 60);

        return $url;
    }

    /**
     * Get the YouTube videos related to this song.
     *
     * @param string $youTubePageToken The YouTube page token, for pagination purpose.
     *
     * @return @return object|false
     */
    public function getRelatedYouTubeVideos($youTubePageToken = '')
    {
        return YouTube::searchVideosRelatedToSong($this, $youTubePageToken);
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
        // We don't use nl2br() here, because the function actually preserves line breaks -
        // it just _appends_ a "<br />" after each of them. This would cause our client
        // implementation of br2nl to fail with duplicated line breaks.
        return str_replace(["\r\n", "\r", "\n"], '<br />', $value);
    }

    /**
     * Get the correct artist of the song.
     * If it's part of a compilation, that would be the contributing artist.
     * Otherwise, it's the album artist.
     *
     * @return Artist
     */
    public function getArtistAttribute()
    {
        return $this->contributing_artist_id
            ? $this->contributingArtist
            : $this->album->artist;
    }

    /**
     * Determine if the song is an AWS S3 Object.
     *
     * @return bool
     */
    public function isS3ObjectAttribute()
    {
        return strpos($this->path, 's3://') === 0;
    }

    /**
     * Get the bucket and key name of an S3 object.
     *
     * @return bool|array
     */
    public function getS3ParamsAttribute()
    {
        if (!preg_match('/^s3:\\/\\/(.*)/', $this->path, $matches)) {
            return false;
        }

        list($bucket, $key) = explode('/', $matches[1], 2);

        return compact('bucket', 'key');
    }
}
