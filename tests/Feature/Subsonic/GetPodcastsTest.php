<?php

namespace Tests\Feature\Subsonic;

use App\Http\Responses\Subsonic\Resources\PodcastChannelResource;
use App\Http\Responses\Subsonic\Resources\PodcastEpisodeResource;
use App\Models\Podcast;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetPodcastsTest extends TestCase
{
    #[Test]
    public function listsSubscribedPodcasts(): void
    {
        $user = create_user();
        $subscribed = Podcast::factory()->createOne();
        $subscribed->subscribers()->attach($user);

        $otherUsersPodcast = Podcast::factory()->createOne();
        $otherUsersPodcast->subscribers()->attach(create_user());

        $response = $this
            ->getJson(self::urlFor($user, ['includeEpisodes' => 'false']))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok')
            ->assertJsonStructure([
                'subsonic-response' => [
                    'podcasts' => [
                        'channel' => [
                            '*' => PodcastChannelResource::JSON_STRUCTURE,
                        ],
                    ],
                ],
            ]);

        $channels = $response->json('subsonic-response.podcasts.channel');
        self::assertCount(1, $channels);
        self::assertSame($subscribed->id, $channels[0]['id']);
    }

    #[Test]
    public function includesEpisodesByDefault(): void
    {
        $user = create_user();
        $podcast = Podcast::factory()->createOne();
        $podcast->subscribers()->attach($user);
        Song::factory()->asEpisode()->createOne(['podcast_id' => $podcast->id]);

        $response = $this
            ->getJson(self::urlFor($user))
            ->assertOk()
            ->assertJsonStructure([
                'subsonic-response' => [
                    'podcasts' => [
                        'channel' => [
                            '*' => array_merge(PodcastChannelResource::JSON_STRUCTURE, ['episode' => [
                                '*' => PodcastEpisodeResource::JSON_STRUCTURE,
                            ]]),
                        ],
                    ],
                ],
            ]);

        self::assertCount(1, $response->json('subsonic-response.podcasts.channel.0.episode'));
    }

    #[Test]
    public function filtersToSingleChannelById(): void
    {
        $user = create_user();
        $targetPodcast = Podcast::factory()->createOne();
        $otherPodcast = Podcast::factory()->createOne();
        $targetPodcast->subscribers()->attach($user);
        $otherPodcast->subscribers()->attach($user);

        $channels = $this
            ->getJson(self::urlFor($user, ['id' => $targetPodcast->id, 'includeEpisodes' => 'false']))
            ->assertOk()
            ->json('subsonic-response.podcasts.channel');

        self::assertCount(1, $channels);
        self::assertSame($targetPodcast->id, $channels[0]['id']);
    }

    /** @param array<string, scalar> $extra */
    private static function urlFor(User $user, array $extra = []): string
    {
        return '/rest/getPodcasts.view?'
        . Arr::query(array_merge([
            'apiKey' => $user->subsonic_api_key,
            'f' => 'json',
        ], $extra));
    }
}
