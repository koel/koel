<?php

namespace App\Ai\Services;

use App\Ai\AiRequestContext;
use App\Enums\FavoriteableType;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\PodcastRepository;
use App\Repositories\RadioStationRepository;
use App\Repositories\SongRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laravel\Ai\Tools\Request;

class FavoriteableEntityResolver
{
    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly RadioStationRepository $radioStationRepository,
        private readonly PodcastRepository $podcastRepository,
    ) {}

    public function resolve(FavoriteableType $type, Request $request, AiRequestContext $context): Collection
    {
        if (isset($request['query'])) {
            return match ($type) {
                FavoriteableType::ALBUM => $this->albumRepository->search($request['query'], 1, $context->user),
                FavoriteableType::ARTIST => $this->artistRepository->search($request['query'], 1, $context->user),
                FavoriteableType::RADIO_STATION => $this->radioStationRepository->search(
                    $request['query'],
                    1,
                    $context->user,
                ),
                FavoriteableType::PODCAST => $this->podcastRepository->search($request['query'], 1, $context->user),
                default => $this->songRepository->search($request['query'], 10, $context->user),
            };
        }

        if ($type === FavoriteableType::PLAYABLE && $context->currentSongId) {
            $song = $this->songRepository->findOne($context->currentSongId, $context->user);

            return $song ? collect([$song]) : collect();
        }

        return collect();
    }

    public function entityName(Model $entity): string
    {
        return $entity->name ?? $entity->title ?? '';
    }
}
