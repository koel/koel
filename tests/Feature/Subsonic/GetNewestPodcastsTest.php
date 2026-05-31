<?php

namespace Tests\Feature\Subsonic;

use App\Http\Responses\Subsonic\Resources\PodcastEpisodeResource;
use App\Models\Podcast;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;

use function Tests\create_user;

class GetNewestPodcastsTest extends SubsonicTestCase
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
            ->getSubsonic('getNewestPodcasts.view', $user)
            ->assertSubsonicOk()
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

        $response = $this->getSubsonic('getNewestPodcasts.view', $user)->assertSubsonicOk();

        self::assertEmpty($response->json('subsonic-response.newestPodcasts.episode'));
    }

    #[Test]
    public function respectsTheCountParameter(): void
    {
        $user = create_user();
        $podcast = Podcast::factory()->createOne();
        $podcast->subscribers()->attach($user);

        Song::factory()
            ->asEpisode()
            ->createMany(array_fill(0, 5, ['podcast_id' => $podcast->id]));

        $episodes = $this->getSubsonic('getNewestPodcasts.view', $user, [
            'count' => 2,
        ])->json('subsonic-response.newestPodcasts.episode');

        self::assertCount(2, $episodes);
    }
}
