<?php

namespace Tests\Integration\Services\Embed;

use App\Enums\EmbeddableType;
use App\Models\Song;
use App\Services\Embed\EmbedService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class EmbedServiceTest extends TestCase
{
    private EmbedService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(EmbedService::class);
    }

    #[Test]
    public function resolveEmbedForEmbeddableReturnsTheSameEmbedForTheSameEmbeddable(): void
    {
        $song = Song::factory()->createOne();
        $user = create_user();

        $embed = $this->service->resolveEmbedForEmbeddable(EmbeddableType::PLAYABLE, $song, $user);

        self::assertSame(EmbeddableType::PLAYABLE, $embed->embeddable_type);
        self::assertSame($song->id, $embed->embeddable_id);
        self::assertTrue($embed->user->is($user));
        self::assertTrue($embed->is($this->service->resolveEmbedForEmbeddable(EmbeddableType::PLAYABLE, $song, $user)));
        self::assertFalse($embed->is($this->service->resolveEmbedForEmbeddable(
            EmbeddableType::PLAYABLE,
            $song,
            create_user(),
        )));
    }

    #[Test]
    public function resolveEmbedForEmbeddableReturnsTheSameFavoritesEmbedWithoutAnEmbeddableId(): void
    {
        $user = create_user();

        $embed = $this->service->resolveEmbedForEmbeddable(EmbeddableType::FAVORITES, $user, $user);

        self::assertNull($embed->embeddable_id);
        self::assertTrue($embed->is($this->service->resolveEmbedForEmbeddable(
            EmbeddableType::FAVORITES,
            $user,
            $user,
        )));
        self::assertFalse($embed->is($this->service->resolveEmbedForEmbeddable(
            EmbeddableType::FAVORITES,
            $user,
            create_user(),
        )));
    }
}
