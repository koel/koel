<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\ResumePodcast;
use App\Models\Podcast;
use App\Models\Song;
use App\Models\User;
use App\Repositories\PodcastRepository;
use App\Services\Podcast\PodcastService;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class ResumePodcastToolTest extends PlusTestCase
{
    private AiAssistantResult $result;
    private User $user;
    private Podcast $podcast;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();
        $this->result = new AiAssistantResult();
        $this->podcast = Podcast::factory()->createOne(['title' => 'Marktplatz']);
        $this->podcast->subscribers()->attach($this->user);

        app()->instance(AiAssistantResult::class, $this->result);
        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
    }

    #[Test]
    public function continueTheCurrentEpisodeWhereTheUserStopped(): void
    {
        $episode = Song::factory()
            ->asEpisode()
            ->createOne(['podcast_id' => $this->podcast->id, 'title' => 'Mieten', 'length' => 1800]);
        app(PodcastService::class)->updateEpisodeProgress($this->user, $episode, 600);
        $this->expectPodcastSearch();

        $response = (string) app()->make(ResumePodcast::class)->handle(new Request(['podcast' => 'marktplatz']));

        self::assertSame('Continuing "Mieten" from "Marktplatz".', $response);
        self::assertSame('play_episode', $this->result->action);
        self::assertTrue($this->result->data['episode']->is($episode));
        self::assertSame(600, $this->result->data['position']);
    }

    #[Test]
    public function startAFinishedEpisodeOver(): void
    {
        $episode = Song::factory()->asEpisode()->createOne(['podcast_id' => $this->podcast->id, 'length' => 1800]);
        app(PodcastService::class)->updateEpisodeProgress($this->user, $episode, 1800);
        $this->expectPodcastSearch();

        app()->make(ResumePodcast::class)->handle(new Request(['podcast' => 'marktplatz']));

        self::assertSame(0, $this->result->data['position']);
    }

    #[Test]
    public function continueAnEpisodeOfUnknownLength(): void
    {
        $episode = Song::factory()->asEpisode()->createOne(['podcast_id' => $this->podcast->id, 'length' => 0]);
        app(PodcastService::class)->updateEpisodeProgress($this->user, $episode, 600);
        $this->expectPodcastSearch();

        app()->make(ResumePodcast::class)->handle(new Request(['podcast' => 'marktplatz']));

        self::assertSame(600, $this->result->data['position']);
    }

    #[Test]
    public function reportAPodcastWithNoStartedEpisode(): void
    {
        $this->expectPodcastSearch();

        $response = (string) app()->make(ResumePodcast::class)->handle(new Request(['podcast' => 'marktplatz']));

        self::assertSame('The user has not started any episode of "Marktplatz" yet.', $response);
        self::assertNull($this->result->action);
    }

    private function expectPodcastSearch(): void
    {
        $this
            ->mock(PodcastRepository::class)
            ->expects('search')
            ->with('marktplatz', 1, $this->user)
            ->andReturn(Collection::make([$this->podcast->fresh('subscribers')]));
    }
}
