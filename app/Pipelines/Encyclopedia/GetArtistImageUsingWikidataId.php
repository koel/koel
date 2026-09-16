<?php

namespace App\Pipelines\Encyclopedia;

use App\Http\Integrations\Wikidata\Requests\GetEntityDataRequest;
use App\Http\Integrations\Wikidata\WikidataConnector;
use Closure;

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
                $fileName = $this->connector
                    ->send(new GetEntityDataRequest($wikidataId))
                    ->json(sprintf(
                        'entities.%s.claims.%s.0.mainsnak.datavalue.value',
                        $wikidataId,
                        self::IMAGE_PROPERTY,
                    ));

                return $fileName ? self::commonsUrl($fileName) : null;
            },
        );

        return $next($image);
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
