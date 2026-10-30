<?php

namespace App\Services\Embed\Resolvers;

use App\Enums\EmbeddableType;
use App\Services\Embed\Resolvers\Contracts\Resolver;

class ResolverRegistry
{
    public function __construct(
        private readonly PlayableResolver $playableResolver,
        private readonly AlbumResolver $albumResolver,
        private readonly ArtistResolver $artistResolver,
        private readonly PlaylistResolver $playlistResolver,
        private readonly FavoritesResolver $favoritesResolver,
    ) {}

    public function getResolverForType(EmbeddableType $type): Resolver
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
