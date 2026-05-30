<?php

namespace Tests\Feature\Subsonic;

use App\Http\Responses\Subsonic\Resources\PodcastEpisodeResource;
use App\Models\Podcast;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetNewestPodcastsTest extends TestCase
{
    #[Test]
    public function returnsNewestEpisodesAcrossSubscriptions(): void
    {
        $user = create_user();

        $subscribedA = Podcast::factory()->createOne();
        $subscribedB = Podcast::factory()->createOne();
        $subscribedA->subscribers()->attach($user);
        $subscribedB->subscribers()->attach($user);

        Song::factory()
            ->asEpisode()
            ->createOne([
                'podcast_id' => $subscribedA->id,
                'created_at' => now()->subDays(2),
                'title' => 'Older episode',
            ]);
        $newer = Song::factory()
            ->asEpisode()
            ->createOne([
                'podcast_id' => $subscribedB->id,
                'created_at' => now()->subHour(),
                'title' => 'Newer episode',
            ]);

        $response = $this
            ->getJson(self::urlFor($user))
            ->assertOk()
            ->assertJsonStructure([
                'subsonic-response' => [
                    'newestPodcasts' => [
                        'episode' => ['*' => PodcastEpisodeResource::JSON_STRUCTURE],
                    ],
                ],
            ]);

        $episodes = $response->json('subsonic-response.newestPodcasts.episode');
        self::assertSame($newer->id, $episodes[0]['id']);
    }

    #[Test]
    public function excludesEpisodesFromOtherUsersSubscriptions(): void
    {
        $user = create_user();
        $otherUser = create_user();

        $otherUsersPodcast = Podcast::factory()->createOne();
        $otherUsersPodcast->subscribers()->attach($otherUser);
        Song::factory()->asEpisode()->createOne(['podcast_id' => $otherUsersPodcast->id]);

        $response = $this->getJson(self::urlFor($user))->assertOk()->assertJsonPath('subsonic-response.status', 'ok');

        self::assertEmpty($response->json('subsonic-response.newestPodcasts.episode'));
    }

    #[Test]
    public function respectsTheCountParameter(): void
    {
        $user = create_user();
        $podcast = Podcast::factory()->createOne();
        $podcast->subscribers()->attach($user);

        Song::factory()->asEpisode()->count(5)->create(['podcast_id' => $podcast->id]);

        $episodes = $this
            ->getJson(self::urlFor($user, ['count' => 2]))
            ->assertOk()
            ->json('subsonic-response.newestPodcasts.episode');

        self::assertCount(2, $episodes);
    }

    /** @param array<string, scalar> $extra */
    private static function urlFor(User $user, array $extra = []): string
    {
        return '/rest/getNewestPodcasts.view?'
        . Arr::query(array_merge([
            'apiKey' => $user->subsonic_api_key,
            'f' => 'json',
        ], $extra));
    }
}
