<?php

namespace Tests\Feature\Subsonic;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Rating;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

use function Tests\create_user;

class UserRatingTest extends SubsonicTestCase
{
    #[Test]
    public function randomSongsCarryTheUsersRatingWithoutQueryingEachSong(): void
    {
        $user = create_user();
        [$loved, $meh, $ratedByOthers] = Song::factory()->count(3)->create(['owner_id' => $user->id])->all();

        self::rate($loved, $user, 5);
        self::rate($meh, $user, 2);
        self::rate($ratedByOthers, create_user(), 4);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $songs = $this->getSubsonic('getRandomSongs.view', $user, [
            'size' => 10,
        ])->assertSubsonicOk()->json('subsonic-response.randomSongs.song');

        self::assertNoRatingLookups();
        self::assertEquals([$loved->id => 5, $meh->id => 2, $ratedByOthers->id => null], self::userRatingsById($songs));
    }

    #[Test]
    public function albumListCarriesTheUsersRatingWithoutQueryingEachAlbum(): void
    {
        $user = create_user();
        [$loved, $meh, $ratedByOthers] = Album::factory()->count(3)->create(['user_id' => $user->id])->all();

        self::rate($loved, $user, 5);
        self::rate($meh, $user, 2);
        self::rate($ratedByOthers, create_user(), 4);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $albums = $this->getSubsonic('getAlbumList2.view', $user, [
            'type' => 'newest',
            'size' => 10,
        ])->assertSubsonicOk()->json('subsonic-response.albumList2.album');

        self::assertNoRatingLookups();
        self::assertEquals(
            [$loved->id => 5, $meh->id => 2, $ratedByOthers->id => null],
            self::userRatingsById($albums),
        );
    }

    #[Test]
    public function artistIndexCarriesTheUsersRatingWithoutQueryingEachArtist(): void
    {
        $user = create_user();
        [$loved, $meh, $ratedByOthers] = Artist::factory()->count(3)->create(['user_id' => $user->id])->all();

        foreach ([$loved, $meh, $ratedByOthers] as $artist) {
            Album::factory()->createOne([
                'artist_id' => $artist->id,
                'artist_name' => $artist->name,
                'user_id' => $user->id,
            ]);
        }

        self::rate($loved, $user, 5);
        self::rate($meh, $user, 2);
        self::rate($ratedByOthers, create_user(), 4);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $indexes = $this
            ->getSubsonic('getArtists.view', $user)
            ->assertSubsonicOk()
            ->json('subsonic-response.artists.index');

        self::assertNoRatingLookups();
        self::assertEquals(
            [$loved->id => 5, $meh->id => 2, $ratedByOthers->id => null],
            self::userRatingsById(collect($indexes)->flatMap(static fn (array $index) => $index['artist'])->all()),
        );
    }

    #[Test]
    public function createdPlaylistEntriesCarryTheUsersRating(): void
    {
        $user = create_user();
        [$loved, $unrated] = Song::factory()->count(2)->create(['owner_id' => $user->id])->all();

        self::rate($loved, $user, 5);

        $entries = $this
            ->getJson(
                "/rest/createPlaylist.view?apiKey={$user->subsonic_api_key}"
                . "&f=json&name=Rated+Mix&songId={$loved->id}&songId={$unrated->id}",
            )
            ->assertSubsonicOk()
            ->json('subsonic-response.playlist.entry');

        self::assertSame([$loved->id => 5, $unrated->id => null], self::userRatingsById($entries));
    }

    private static function rate(Model $rateable, User $user, int $rating): void
    {
        Rating::factory()->for($user)->for($rateable, 'rateable')->createOne(['rating' => $rating]);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<string, ?int>
     */
    private static function userRatingsById(array $items): array
    {
        return collect($items)->mapWithKeys(static fn (array $item) => [
            $item['id'] => $item['userRating'] ?? null,
        ])->all();
    }

    private static function assertNoRatingLookups(): void
    {
        $ratingLookups = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(
                static fn (string $sql): bool => (
                    preg_match('/^select\s[^(]*\sfrom\s+["`]?ratings["`]?\s/i', $sql) === 1
                ),
            )
            ->values()
            ->all();

        self::assertSame([], $ratingLookups);
    }
}
