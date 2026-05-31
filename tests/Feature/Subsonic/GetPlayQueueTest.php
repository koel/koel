<?php

namespace Tests\Feature\Subsonic;

use App\Http\Responses\Subsonic\Resources\PlayQueueResource;
use App\Models\QueueState;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetPlayQueueTest extends TestCase
{
    #[Test]
    public function returnsSavedQueueForUser(): void
    {
        $user = create_user();
        $songs = Song::factory()->count(2)->create();

        QueueState::query()->create([
            'user_id' => $user->id,
            'song_ids' => $songs->pluck('id')->all(),
            'current_song_id' => $songs[0]->id,
            'playback_position' => 42, // seconds
            'changed_by' => 'Feishin',
        ]);

        $response = $this
            ->getJson(self::urlFor($user))
            ->assertOk()
            ->assertJsonStructure([
                'subsonic-response' => [
                    'playQueue' => PlayQueueResource::JSON_STRUCTURE,
                ],
            ]);

        self::assertSame($user->email, $response->json('subsonic-response.playQueue.username'));
        self::assertSame('Feishin', $response->json('subsonic-response.playQueue.changedBy'));
        self::assertSame($songs[0]->id, $response->json('subsonic-response.playQueue.current'));
        self::assertSame(42_000, $response->json('subsonic-response.playQueue.position'));
        self::assertCount(2, $response->json('subsonic-response.playQueue.entry'));
    }

    #[Test]
    public function returnsEmptyResponseWhenNoQueueSaved(): void
    {
        $user = create_user();

        $this
            ->getJson(self::urlFor($user))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok')
            ->assertJsonMissingPath('subsonic-response.playQueue');
    }

    #[Test]
    public function fallsBackToKoelWhenChangedByMissing(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne();

        QueueState::query()->create([
            'user_id' => $user->id,
            'song_ids' => [$song->id],
            'current_song_id' => $song->id,
            'playback_position' => 0,
        ]);

        $this
            ->getJson(self::urlFor($user))
            ->assertOk()
            ->assertJsonPath('subsonic-response.playQueue.changedBy', 'koel');
    }

    private static function urlFor(User $user): string
    {
        return '/rest/getPlayQueue.view?'
        . Arr::query([
            'apiKey' => $user->subsonic_api_key,
            'f' => 'json',
        ]);
    }
}
