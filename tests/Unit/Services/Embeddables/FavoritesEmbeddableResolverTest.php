<?php

namespace Tests\Unit\Services\Embeddables;

use App\Services\Embeddables\FavoritesEmbeddableResolver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class FavoritesEmbeddableResolverTest extends TestCase
{
    #[Test]
    public function cannotEmbedAnotherUsersFavorites(): void
    {
        $resolver = app(FavoritesEmbeddableResolver::class);

        self::assertFalse($resolver->canEmbed(create_user(), create_user()));
    }
}
