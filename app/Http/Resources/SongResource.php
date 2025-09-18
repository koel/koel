<?php

namespace App\Http\Resources;

use App\Facades\License;
use App\Models\Song;
use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SongResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'title',
        'lyrics',
        'album_id',
        'album_name',
        'artist_id',
        'artist_name',
        'album_artist_id',
        'album_artist_name',
        'album_cover',
        'length',
        'liked',
        'play_count',
        'track',
        'genre',
        'year',
        'disc',
        'is_public',
        'created_at',
    ];

    public const PAGINATION_JSON_STRUCTURE = [
        'data' => [
            0 => self::JSON_STRUCTURE,
        ],
        'links' => [
            'first',
            'last',
            'prev',
            'next',
        ],
        'meta' => [
            'current_page',
            'from',
            'path',
            'per_page',
            'to',
        ],
    ];

    private ?User $user;

    public function __construct(protected Song $song)
    {
        parent::__construct($song);
    }

    /**
     * @param Collection<Song>|Paginator<Song> $resource
     *
     * @return SongResourceCollection
     */
    public static function collection($resource) // @phpcs:ignore
    {
        return SongResourceCollection::make($resource);
    }

    public function for(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /** @inheritDoc */
    public function toArray(Request $request): array
    {
        $isPlus = once(static fn () => License::isPlus());
        $user = $this->user ?? once(static fn () => auth()->user());
        $embedding = $request->routeIs('embeds.payload');

        $data = [
            'type' => Str::plural($this->song->type->value),
            'id' => $this->song->id,
            'title' => $this->song->title,
            'lyrics' => $this->unless($embedding, $this->song->lyrics),
            'album_id' => $this->unless($embedding, $this->song->album_id),
            'album_name' => $this->song->album_name,
            'artist_id' => $this->unless($embedding, $this->song->artist_id),
            'artist_name' => $this->song->artist?->name,
            'album_artist_id' => $this->unless($embedding, $this->song->album_artist?->id),
            'album_artist_name' => $this->unless($embedding, $this->song->album_artist?->name),
            'album_cover' => $this->song->album?->cover,
            'length' => $this->song->length,
            'liked' => $this->unless($embedding, $this->song->favorite), // backwards compatibility
            'favorite' => $this->unless($embedding, $this->song->favorite),
            'play_count' => $this->unless($embedding, (int) $this->song->play_count),
            'track' => $this->song->track,
            'disc' => $this->unless($embedding, $this->song->disc),
            'genre' => $this->unless($embedding, $this->song->genre),
            'year' => $this->unless($embedding, $this->song->year),
            'is_public' => $this->unless($embedding, $this->song->is_public),
            'created_at' => $this->unless($embedding, $this->song->created_at),
            'embed_stream_url' => $this->when(
                $embedding,
                fn () => URL::temporarySignedRoute('embeds.stream', now()->addDay(), [
                    'song' => $this->song->id,
                    'embed' => $request->route('embed')->id, // @phpstan-ignore-line
                    'options' => $request->route('options'),
                ]),
            ),
        ];

        if ($this->song->isEpisode()) {
            $data += [
                'episode_description' => $this->unless($embedding, $this->song->episode_metadata->description),
                'episode_link' => $this->song->episode_metadata->link,
                'episode_image' => $this->song->episode_metadata->image ?? $this->song->podcast->image,
                'podcast_id' => $this->unless($embedding, $this->song->podcast->id),
                'podcast_title' => $this->song->podcast->title,
                'podcast_author' => $this->song->podcast->metadata->author,
            ];
        } else {
            $data += [
                'owner_id' => $this->unless($embedding, $this->song->owner->public_id),
                'is_external' => $this->unless(
                    $embedding,
                    fn () => $isPlus && !$this->song->ownedBy($user),
                ),
            ];
        }

        return $data;
    }
}
