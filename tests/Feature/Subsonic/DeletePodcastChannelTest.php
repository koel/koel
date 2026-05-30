<?php

namespace Tests\Feature\Subsonic;

use App\Models\Podcast;
use App\Models\User;
use App\Services\Podcast\PodcastService;
use Illuminate\Support\Arr;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class DeletePodcastChannelTest extends TestCase
{
    #[Test]
    public function unsubscribesFromSubscribedPodcast(): void
    {
        $user = create_user();
        $podcast = Podcast::factory()->createOne();
        $podcast->subscribers()->attach($user);

        $service = $this->mock(PodcastService::class);
        $service
            ->expects('unsubscribeUserFromPodcast')
            ->once()
            ->with(
                Mockery::on(static fn (User $u) => $u->is($user)),
                Mockery::on(static fn (Podcast $p) => $p->is($podcast)),
            );

        $this->getJson(self::urlFor($user, $podcast->id))->assertOk()->assertJsonPath('subsonic-response.status', 'ok');
    }

    #[Test]
    public function unknownIdReturnsCode70(): void
    {
        $user = create_user();

        $this
            ->getJson(self::urlFor($user, 'nonexistent-id'))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 70);
    }

    private static function urlFor(User $user, string $id): string
    {
        return '/rest/deletePodcastChannel.view?'
        . Arr::query([
            'apiKey' => $user->subsonic_api_key,
            'f' => 'json',
            'id' => $id,
        ]);
    }
}
