<?php

namespace App\Models;

use App\Events\LibraryChanged;
use App\Traits\SupportsDeleteWhereIDsNotIn;
use AWS;
use Aws\AwsClient;
use Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Lastfm;
use YouTube;

/**
 * @property string path
 * @property string title
 * @property Album  album
 * @property Artist artist
 * @property string s3_params
 * @property float  length
 * @property string lyrics
 * @property int    track
 * @property int    disc
 * @property int    album_id
 * @property int    id
 * @property int    artist_id
 */
class Song extends Model
{
    use SupportsDeleteWhereIDsNotIn;

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
        'disc' => 'int',
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * A song belongs to an artist.
     *
     * @return BelongsTo
     */
    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    /**
     * A song belongs to a album.
     *
     * @return BelongsTo
     */
    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    /**
     * A song can belong to many playlists.
     *
     * @return BelongsToMany
     */
    public function playlists()
    {
        return $this->belongsToMany(Playlist::class);
    }

    /**
     * Scrobble the song using Last.fm service.
     *
     * @param User   $user
     * @param string $timestamp The UNIX timestamp in which the song started playing.
     *
     * @return mixed
     */
    public function scrobble(User $user, $timestamp)
    {
        // Don't scrobble the unknown guys. No one knows them.
        if ($this->artist->is_unknown) {
            return false;
        }

        // If the current user hasn't connected to Last.fm, don't do shit.
        if (!$user->connectedToLastfm()) {
            return false;
        }

        return Lastfm::scrobble(
            $this->artist->name,
            $this->title,
            $timestamp,
            $this->album->name === Album::UNKNOWN_NAME ? '' : $this->album->name,
            $user->lastfm_session_key
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
         * A collection of the updated songs.
         *
         * @var Collection
         */
        $updatedSongs = collect();

        $ids = (array) $ids;
        // If we're updating only one song, take into account the title, lyrics, and track number.
        $single = count($ids) === 1;

        foreach ($ids as $id) {
            if (!$song = self::with('album', 'album.artist')->find($id)) {
                continue;
            }

            $updatedSongs->push($song->updateSingle(
                $single ? trim($data['title']) : $song->title,
                trim($data['albumName'] ?: $song->album->name),
                trim($data['artistName']) ?: $song->artist->name,
                $single ? trim($data['lyrics']) : $song->lyrics,
                $single ? (int) $data['track'] : $song->track,
                (int) $data['compilationState']
            ));
        }

        // Our library may have been changed. Broadcast an event to tidy it up if need be.
        if ($updatedSongs->count()) {
            event(new LibraryChanged());
        }

        return [
            'artists' => Artist::whereIn('id', $updatedSongs->pluck('artist_id'))->get(),
            'albums' => Album::whereIn('id', $updatedSongs->pluck('album_id'))->get(),
            'songs' => $updatedSongs,
        ];
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
        if ($artistName === Artist::VARIOUS_NAME) {
            // If the artist name is "Various Artists", it's a compilation song no matter what.
            $compilationState = 1;
            // and since we can't determine the real contributing artist, it's "Unknown"
            $artistName = Artist::UNKNOWN_NAME;
        }

        $artist = Artist::get($artistName);

        switch ($compilationState) {
            case 1: // ALL, or forcing compilation status to be Yes
                $isCompilation = true;
                break;
            case 2: // Keep current compilation status
                $isCompilation = $this->album->artist_id === Artist::VARIOUS_ID;
                break;
            default:
                $isCompilation = false;
                break;
        }

        $album = Album::get($artist, $albumName, $isCompilation);

        $this->artist_id = $artist->id;
        $this->album_id = $album->id;
        $this->title = $title;
        $this->lyrics = $lyrics;
        $this->track = $track;

        $this->save();

        // Clean up unnecessary data from the object
        unset($this->album);
        unset($this->artist);
        // and make sure the lyrics is shown
        $this->makeVisible('lyrics');

        return $this;
    }

    /**
     * Scope a query to only include songs in a given directory.
     *
     * @param Builder $query
     * @param string  $path  Full path of the directory
     *
     * @return Builder
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
     * @return Collection|array
     */
    public static function getFavorites(User $user, $toArray = false)
    {
        $songs = Interaction::whereUserIdAndLike($user->id, true)
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
        return Cache::remember("OSUrl/{$this->id}", 60, function () use ($s3) {
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

            return $url;
        });
    }

    /**
     * Get the YouTube videos related to this song.
     *
     * @param string $youTubePageToken The YouTube page token, for pagination purpose.
     *
     * @return false|object
     */
    public function getRelatedYouTubeVideos($youTubePageToken = '')
    {
        return YouTube::searchVideosRelatedToSong($this, $youTubePageToken);
    }

    /**
     * Sometimes the tags extracted from getID3 are HTML entity encoded.
     * This makes sure they are always sane.
     *
     * @param string $value
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = html_entity_decode($value);
    }

    /**
     * Some songs don't have a title.
     * Fall back to the file name (without extension) for such.
     *
     * @param string $value
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

    /**
     * Return the ID of the song when it's converted to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->id;
    }
}
