<?php

namespace App\Services\Embeddables;

use App\Enums\EmbeddableType;
use App\Services\Embeddables\Contracts\EmbeddableResolver;

class EmbeddableResolverRegistry
{
    public function __construct(
        private readonly PlayableEmbeddableResolver $playableResolver,
        private readonly AlbumEmbeddableResolver $albumResolver,
        private readonly ArtistEmbeddableResolver $artistResolver,
        private readonly PlaylistEmbeddableResolver $playlistResolver,
        private readonly FavoritesEmbeddableResolver $favoritesResolver,
    ) {}

    public function getResolverForType(EmbeddableType $type): EmbeddableResolver
    {
        return match ($type) {
            EmbeddableType::PLAYABLE => $this->playableResolver,
            EmbeddableType::ALBUM => $this->albumResolver,
            EmbeddableType::ARTIST => $this->artistResolver,
            EmbeddableType::PLAYLIST => $this->playlistResolver,
            EmbeddableType::FAVORITES => $this->favoritesResolver,
        };
    }
}
