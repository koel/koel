<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use App\Services\EmbedService;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /** @return array<mixed> */
    public static function provideEmbeddableData(): array
    {
        return [
            ['playable', Song::class],
            ['playlist', Playlist::class],
            ['album', Album::class],
            ['artist', Artist::class],
        ];
    }

    #[Test]
    #[DataProvider('provideEmbeddableData')]
    /** @param class-string<Song|Playlist|Album|Artist> $modelClass */
    public function resolveEmbedForEmbeddable(string $type, string $modelClass): void
    {
        $embeddable = $modelClass::factory()->create();
        $user = create_user();

        $embed = $this->service->resolveEmbedForEmbeddable($embeddable, $user);
        self::assertSame($type, $embed->embeddable_type);
        self::assertTrue($embed->embeddable->is($embeddable));
        self::assertTrue($embed->user->is($user));

        $secondEmbed = $this->service->resolveEmbedForEmbeddable($embeddable, $user);

        self::assertTrue($embed->is($secondEmbed));
    }
}
