<?php

namespace Tests\Integration\KoelPlus\Services\Playlist;

use App\Helpers\Uuid;
use App\Models\Song;
use App\Services\Playlist\SmartPlaylistService;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_playlist;
use function Tests\create_user;

class SmartPlaylistServiceTest extends PlusTestCase
{
    #[Test]
    public function keepOtherUsersPrivateSongsOutOfEveryRuleGroup(): void
    {
        $user = create_user();
        $someoneElse = create_user();

        $ownSong = Song::factory()->for($user, 'owner')->private()->createOne(['title' => 'Foo']);
        $publicSong = Song::factory()->for($someoneElse, 'owner')->public()->createOne(['title' => 'Bar']);
        Song::factory()->for($someoneElse, 'owner')->private()->createOne(['title' => 'Bar']);

        $playlist = create_playlist(['rules' => [
            self::makeTitleGroup('Foo'),
            self::makeTitleGroup('Bar'),
        ]]);

        $playlist->users()->detach();
        $playlist->users()->attach($user, ['role' => 'owner']);

        self::assertEqualsCanonicalizing(
            [$ownSong->id, $publicSong->id],
            app(SmartPlaylistService::class)->getSongs($playlist, $user)->modelKeys(),
        );
    }

    /** @return array<array-key, mixed> */
    private static function makeTitleGroup(string $title): array
    {
        return [
            'id' => Uuid::generate(),
            'rules' => [['id' => Uuid::generate(), 'model' => 'title', 'operator' => 'is', 'value' => [$title]]],
        ];
    }
}
