<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class MoveFavoriteSongsTest extends TestCase
{
    #[Test]
    public function moveFavoritesAfterTarget(): void
    {
        $user = create_user();
        $songs = Song::factory()->createMany(4);

        foreach ($songs as $index => $song) {
            Favorite::factory()->for($user)->createOne([
                'favoriteable_id' => $song->id,
                'favoriteable_type' => 'playable',
                'position' => $index,
            ]);
        }

        // Move song[0] after song[2]: order should become [1, 2, 0, 3]
        $this->postAs(
            'api/favorites/move',
            [
                'songs' => [$songs[0]->id],
                'target' => $songs[2]->id,
                'placement' => 'after',
            ],
            $user,
        )->assertNoContent();

        $positions = Favorite::query()
            ->where('user_id', $user->id)
            ->orderBy('position')
            ->pluck('favoriteable_id')
            ->toArray();

        self::assertSame([$songs[1]->id, $songs[2]->id, $songs[0]->id, $songs[3]->id], $positions);
    }

    #[Test]
    public function moveFavoritesBeforeTarget(): void
    {
        $user = create_user();
        $songs = Song::factory()->createMany(4);

        foreach ($songs as $index => $song) {
            Favorite::factory()->for($user)->createOne([
                'favoriteable_id' => $song->id,
                'favoriteable_type' => 'playable',
                'position' => $index,
            ]);
        }

        // Move song[3] before song[1]: order should become [0, 3, 1, 2]
        $this->postAs(
            'api/favorites/move',
            [
                'songs' => [$songs[3]->id],
                'target' => $songs[1]->id,
                'placement' => 'before',
            ],
            $user,
        )->assertNoContent();

        $positions = Favorite::query()
            ->where('user_id', $user->id)
            ->orderBy('position')
            ->pluck('favoriteable_id')
            ->toArray();

        self::assertSame([$songs[0]->id, $songs[3]->id, $songs[1]->id, $songs[2]->id], $positions);
    }

    #[Test]
    public function moveMultipleFavorites(): void
    {
        $user = create_user();
        $songs = Song::factory()->createMany(5);

        foreach ($songs as $index => $song) {
            Favorite::factory()->for($user)->createOne([
                'favoriteable_id' => $song->id,
                'favoriteable_type' => 'playable',
                'position' => $index,
            ]);
        }

        // Move songs [0, 1] after song[3]: order should become [2, 3, 0, 1, 4]
        $this->postAs(
            'api/favorites/move',
            [
                'songs' => [$songs[0]->id, $songs[1]->id],
                'target' => $songs[3]->id,
                'placement' => 'after',
            ],
            $user,
        )->assertNoContent();

        $positions = Favorite::query()
            ->where('user_id', $user->id)
            ->orderBy('position')
            ->pluck('favoriteable_id')
            ->toArray();

        self::assertSame(
            [
                $songs[2]->id,
                $songs[3]->id,
                $songs[0]->id,
                $songs[1]->id,
                $songs[4]->id,
            ],
            $positions,
        );
    }

    #[Test]
    public function cannotMoveAnotherUsersFavorites(): void
    {
        $user = create_user();
        $otherUser = create_user();

        $songs = Song::factory()->createMany(2);

        // Songs are favorited by $otherUser, not $user
        foreach ($songs as $index => $song) {
            Favorite::factory()->for($otherUser)->createOne([
                'favoriteable_id' => $song->id,
                'favoriteable_type' => 'playable',
                'position' => $index,
            ]);
        }

        $this->postAs(
            'api/favorites/move',
            [
                'songs' => [$songs[0]->id],
                'target' => $songs[1]->id,
                'placement' => 'after',
            ],
            $user,
        )->assertUnprocessable();
    }

    #[Test]
    public function cannotMoveTargetThatIsAlsoBeingMoved(): void
    {
        $user = create_user();
        $songs = Song::factory()->createMany(3);

        foreach ($songs as $index => $song) {
            Favorite::factory()->for($user)->createOne([
                'favoriteable_id' => $song->id,
                'favoriteable_type' => 'playable',
                'position' => $index,
            ]);
        }

        $this->postAs(
            'api/favorites/move',
            [
                'songs' => [$songs[0]->id, $songs[1]->id],
                'target' => $songs[1]->id,
                'placement' => 'after',
            ],
            $user,
        )->assertUnprocessable();
    }
}
