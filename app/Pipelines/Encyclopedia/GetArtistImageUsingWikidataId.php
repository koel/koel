<?php

namespace App\Pipelines\Encyclopedia;

use App\Http\Integrations\Wikidata\Requests\GetEntityDataRequest;
use App\Http\Integrations\Wikidata\WikidataConnector;
use Closure;
use Illuminate\Support\Arr;

class GetArtistImageUsingWikidataId
{
    use TriesRemember;

    private const string IMAGE_PROPERTY = 'P18';
    private const int IMAGE_WIDTH = 640;

    public function __construct(
        private readonly WikidataConnector $connector,
    ) {}

    public function __invoke(?string $wikidataId, Closure $next): mixed
    {
        if (!$wikidataId) {
            return $next(null);
        }

        $image = self::tryRememberForever(
            key: cache_key('artist image from wikidata id', $wikidataId),
            nothingFoundTtl: now()->addWeek(),
            callback: function () use ($wikidataId): ?string {
                $statements = $this->connector
                    ->send(new GetEntityDataRequest($wikidataId))
                    ->json(sprintf('entities.%s.claims.%s', $wikidataId, self::IMAGE_PROPERTY)) ?? [];

                $fileName = self::pickFileName($statements);

                return $fileName ? self::commonsUrl($fileName) : null;
            },
        );

        return $next($image);
    }

    /**
     * Wikidata allows several images per entity: a preferred one outranks the rest, and a deprecated
     * one must never be used, whatever its position in the list.
     *
     * @param array<int, array<string, mixed>> $statements
     */
    private static function pickFileName(array $statements): ?string
    {
        $usable = collect($statements)
            ->filter(static fn (array $statement): bool => Arr::get($statement, 'mainsnak.snaktype') === 'value')
            ->reject(static fn (array $statement): bool => Arr::get($statement, 'rank') === 'deprecated');

        $statement = $usable->firstWhere('rank', 'preferred') ?? $usable->first();

        return $statement ? Arr::get($statement, 'mainsnak.datavalue.value') : null;
    }

    private static function commonsUrl(string $fileName): string
    {
        return sprintf(
            'https://commons.wikimedia.org/wiki/Special:FilePath/%s?width=%d',
            rawurlencode($fileName),
            self::IMAGE_WIDTH,
        );
    }
}
