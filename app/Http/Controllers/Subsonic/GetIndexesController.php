<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\Resources\ArtistResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\ArtistRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class GetIndexesController extends Controller
{
    private const string IGNORED_ARTICLES = 'The El La Los Las Le Les';

    public function __construct(
        private readonly ArtistRepository $artistRepository,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        $ignored = array_map(Str::lower(...), explode(' ', self::IGNORED_ARTICLES));
        $artists = $this->artistRepository->getAll();

        $indexes = $artists
            ->groupBy(static fn (Artist $artist) => self::indexLetter($artist->name, $ignored))
            ->sortKeys()
            ->map(static fn (Collection $group, string $letter) => [
                'name' => $letter,
                'artist' => $group->map(static fn (Artist $artist) => ArtistResource::toArray($artist, $user))->all(),
            ])
            ->values()
            ->all();

        return SubsonicResponse::ok([
            'indexes' => [
                'ignoredArticles' => self::IGNORED_ARTICLES,
                'lastModified' => (int) ($artists->max('updated_at')?->getTimestampMs() ?? now()->getTimestampMs()),
                'index' => $indexes,
            ],
        ]);
    }

    /** @param array<string> $lowerArticles */
    private static function indexLetter(string $name, array $lowerArticles): string
    {
        $name = Str::trim($name);
        $lower = Str::lower($name);

        foreach ($lowerArticles as $article) {
            $prefix = $article . ' ';

            if (Str::startsWith($lower, $prefix)) {
                $name = Str::substr($name, Str::length($prefix));
                break;
            }
        }

        $first = Str::upper(Str::substr(trim($name), 0, 1));

        return ctype_alpha($first) ? $first : '#';
    }
}
