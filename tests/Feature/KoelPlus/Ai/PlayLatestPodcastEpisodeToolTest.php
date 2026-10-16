<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\PlayLatestPodcastEpisode;
use App\Models\Podcast;
use App\Models\Song;
use App\Models\User;
use App\Repositories\PodcastRepository;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class PlayLatestPodcastEpisodeToolTest extends PlusTestCase
{
    private AiAssistantResult $result;
    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();
        $this->result = new AiAssistantResult();

        app()->instance(AiAssistantResult::class, $this->result);
        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
    }

    #[Test]
    public function playTheNewestEpisode(): void
    {
        $podcast = Podcast::factory()->createOne(['title' => 'Marktplatz']);
        $podcast->subscribers()->attach($this->user);
        Song::factory()->asEpisode()->createOne(['podcast_id' => $podcast->id, 'created_at' => now()->subWeek()]);
        $newest = Song::factory()
            ->asEpisode()
            ->createOne(['podcast_id' => $podcast->id, 'title' => 'Mieten', 'created_at' => now()]);

        $this
            ->mock(PodcastRepository::class)
            ->expects('search')
            ->with('marktplatz', 1, $this->user)
            ->andReturn(Collection::make([$podcast]));

        $response = (string) app()
            ->make(PlayLatestPodcastEpisode::class)
            ->handle(new Request(['podcast' => 'marktplatz']));

        self::assertSame('Playing "Mieten" from "Marktplatz".', $response);
        self::assertSame('play_episode', $this->result->action);
        self::assertTrue($this->result->data['episode']->is($newest));
        self::assertSame(0, $this->result->data['position']);
    }

    #[Test]
    public function reportAPodcastTheUserDoesNotFollow(): void
    {
        $this->mock(PodcastRepository::class)->expects('search')->andReturn(Collection::make());

        $response = (string) app()
            ->make(PlayLatestPodcastEpisode::class)
            ->handle(new Request(['podcast' => 'marktplatz']));

        self::assertSame('The user is not subscribed to a podcast matching "marktplatz".', $response);
        self::assertNull($this->result->action);
    }
}
