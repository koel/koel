<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Renders the indexed-artists structure shared by `<indexes>` (`getIndexes`)
 * and `<artists>` (`getArtists`). The caller decides the wrapper element name
 * and adds wrapper-only fields such as `lastModified` for `<indexes>`.
 */
final class IndexesResource
{
    public const string IGNORED_ARTICLES = 'The El La Los Las Le Les';

    public const array JSON_STRUCTURE = [
        'ignoredArticles',
        'index',
    ];

    /**
     * @param EloquentCollection<int, Artist> $artists
     *
     * @return array{
     *     ignoredArticles: string,
     *     index: list<array{name: string, artist: list<array<string, mixed>>}>,
     * }
     */
    public static function toArray(EloquentCollection $artists, User $user): array
    {
        $ignored = array_map(Str::lower(...), explode(' ', self::IGNORED_ARTICLES));

        $index = $artists
            ->groupBy(static fn (Artist $artist) => self::indexLetter($artist->name, $ignored))
            ->sortKeys()
            ->map(static fn (Collection $group, string $letter) => [
                'name' => $letter,
                'artist' => $group->map(static fn (Artist $artist) => ArtistResource::toArray($artist, $user))->all(),
            ])
            ->values()
            ->all();

        return [
            'ignoredArticles' => self::IGNORED_ARTICLES,
            'index' => $index,
        ];
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
