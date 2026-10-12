<?php

namespace App\Services\Integrations;

use App\Http\Integrations\CoverArtArchive\CoverArtArchiveConnector;
use App\Http\Integrations\CoverArtArchive\Requests\GetReleaseCoverRequest;
use App\Http\Integrations\CoverArtArchive\Requests\GetReleaseGroupCoverRequest;
use App\Models\Album;
use App\Pipelines\Encyclopedia\GetReleaseGroupMbidUsingReleaseMbid;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Pipeline;
use Illuminate\Support\Str;
use Saloon\Http\Request;

class CoverArtArchiveService
{
    private const string PREFERRED_THUMBNAIL = '1200';

    public function __construct(
        private readonly CoverArtArchiveConnector $connector,
    ) {}

    public static function enabled(): bool
    {
        return MusicBrainzService::enabled();
    }

    public function tryGetAlbumCover(Album $album): ?string
    {
        if (!self::enabled() || !$album->mbid) {
            return null;
        }

        $cover = $this->findFrontCover(new GetReleaseCoverRequest($album->mbid));

        if ($cover) {
            return $cover;
        }

        $releaseGroupMbid = $this->findReleaseGroupMbid($album->mbid);

        return $releaseGroupMbid ? $this->findFrontCover(new GetReleaseGroupCoverRequest($releaseGroupMbid)) : null;
    }

    private function findFrontCover(Request $request): ?string
    {
        $response = $this->connector->send($request);

        if ($response->failed()) {
            return null;
        }

        $front = Arr::first(
            $response->json('images') ?? [],
            static fn (array $image): bool => (bool) Arr::get($image, 'front'),
        );

        if (!$front) {
            return null;
        }

        $url = Arr::get($front, 'thumbnails.' . self::PREFERRED_THUMBNAIL) ?: Arr::get($front, 'image');

        return $url ? Str::replaceStart('http://', 'https://', $url) : null;
    }

    private function findReleaseGroupMbid(string $releaseMbid): ?string
    {
        return Pipeline::send($releaseMbid)->through([GetReleaseGroupMbidUsingReleaseMbid::class])->thenReturn();
    }
}
