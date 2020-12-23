<?php

namespace App\Models;

use App\Events\LibraryChanged;
use App\Traits\SupportsDeleteWhereIDsNotIn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Laravel\Scout\Searchable;

/**
 * @property string $path
 * @property string $title
 * @property Album $album
 * @property Artist $artist
 * @property array<string> $s3_params
 * @property float $length
 * @property string $lyrics
 * @property int $track
 * @property int $disc
 * @property int $album_id
 * @property string $id
 * @property int $artist_id
 * @property int $mtime
 * @property int $contributing_artist_id
 *
 * @method static self updateOrCreate(array $where, array $params)
 * @method static Builder select(string $string)
 * @method static Builder inDirectory(string $path)
 * @method static self first()
 * @method static EloquentCollection orderBy(...$args)
 * @method static int count()
 * @method static self|null find($id)
 * @method static Builder take(int $count)
 */
class Song extends Model
{
    use HasFactory;
    use Searchable;
    use SupportsDeleteWhereIDsNotIn;

    protected $guarded = [];

    /**
     * Attributes to be hidden from JSON outputs.
     * Here we specify to hide lyrics as well to save some bandwidth (actually, lots of it).
     * Lyrics can then be queried on demand.
     */
    protected $hidden = ['lyrics', 'updated_at', 'path', 'mtime'];

    protected $casts = [
        'length' => 'float',
        'mtime' => 'int',
        'track' => 'int',
        'disc' => 'int',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    /**
     * Update song info.
     *
     * @param array<string> $ids
     * @param array<string> $data the data array, with these supported fields:
     * - title
     * - artistName
     * - albumName
     * - lyrics
     * All of these are optional, in which case the info will not be changed
     * (except for lyrics, which will be emptied)
     *
     * @return Collection|array<Song>
     */
    public static function updateInfo(array $ids, array $data): Collection
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
            /** @var Song|null $song */
            $song = self::with('album', 'album.artist')->find($id);

            if (!$song) {
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

        return $updatedSongs;
    }

    public function updateSingle(
        string $title,
        string $albumName,
        string $artistName,
        string $lyrics,
        int $track,
        int $compilationState
    ): self {
        if ($artistName === Artist::VARIOUS_NAME) {
            // If the artist name is "Various Artists", it's a compilation song no matter what.
            $compilationState = 1;
            // and since we can't determine the real contributing artist, it's "Unknown"
            $artistName = Artist::UNKNOWN_NAME;
        }

        $artist = Artist::getOrCreate($artistName);

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

        $album = Album::getOrCreate($artist, $albumName, $isCompilation);

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
     */
    public function scopeInDirectory(Builder $query, string $path): Builder
    {
        // Make sure the path ends with a directory separator.
        $path = rtrim(trim($path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return $query->where('path', 'LIKE', "$path%");
    }

    /**
     * Sometimes the tags extracted from getID3 are HTML entity encoded.
     * This makes sure they are always sane.
     */
    public function setTitleAttribute(string $value): void
    {
        $this->attributes['title'] = html_entity_decode($value);
    }

    /**
     * Some songs don't have a title.
     * Fall back to the file name (without extension) for such.
     */
    public function getTitleAttribute(?string $value): string
    {
        return $value ?: pathinfo($this->path, PATHINFO_FILENAME);
    }

    /**
     * Prepare the lyrics for displaying.
     */
    public function getLyricsAttribute(string $value): string
    {
        // We don't use nl2br() here, because the function actually preserves line breaks -
        // it just _appends_ a "<br />" after each of them. This would cause our client
        // implementation of br2nl to fail with duplicated line breaks.
        return str_replace(["\r\n", "\r", "\n"], '<br />', $value);
    }

    /**
     * Get the bucket and key name of an S3 object.
     *
     * @return array<string>|null
     */
    public function getS3ParamsAttribute(): ?array
    {
        if (!preg_match('/^s3:\\/\\/(.*)/', $this->path, $matches)) {
            return null;
        }

        [$bucket, $key] = explode('/', $matches[1], 2);

        return compact('bucket', 'key');
    }

    /** @return array<mixed> */
    public function toSearchableArray(): array
    {
        $array = [
            'id' => $this->id,
            'title' => $this->title,
        ];

        if (!$this->artist->is_unknown && !$this->artist->is_various) {
            $array['artist'] = $this->artist->name;
        }

        if (!$this->album->is_unknown) {
            $array['album'] = $this->album->name;
        }

        return $array;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
