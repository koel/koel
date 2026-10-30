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
    public function resolveEmbedReturnsTheSameEmbedForTheSameEmbeddable(): void
    {
        $song = Song::factory()->createOne();
        $user = create_user();

        $embed = $this->service->resolveEmbed(EmbeddableType::PLAYABLE, $song, $user);

        self::assertSame(EmbeddableType::PLAYABLE, $embed->embeddable_type);
        self::assertSame($song->id, $embed->embeddable_id);
        self::assertTrue($embed->user->is($user));
        self::assertTrue($embed->is($this->service->resolveEmbed(EmbeddableType::PLAYABLE, $song, $user)));
        self::assertFalse($embed->is($this->service->resolveEmbed(EmbeddableType::PLAYABLE, $song, create_user())));
    }

    #[Test]
    public function resolveEmbedReturnsTheSameFavoritesEmbedWithoutAnEmbeddableId(): void
    {
        $user = create_user();

        $embed = $this->service->resolveEmbed(EmbeddableType::FAVORITES, $user, $user);

        self::assertNull($embed->embeddable_id);
        self::assertTrue($embed->is($this->service->resolveEmbed(EmbeddableType::FAVORITES, $user, $user)));
        self::assertFalse($embed->is($this->service->resolveEmbed(EmbeddableType::FAVORITES, $user, create_user())));
    }
}
