<?php

namespace Tests\Unit\Services\Embed\Resolvers;

use App\Services\Embed\Resolvers\FavoritesResolver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class FavoritesResolverTest extends TestCase
{
    #[Test]
    public function cannotEmbedAnotherUsersFavorites(): void
    {
        $resolver = app(FavoritesResolver::class);

        self::assertFalse($resolver->canEmbed(create_user(), create_user()));
    }
}
