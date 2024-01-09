<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Models\User;
use Tests\TestCase;

class SongVisibilityTest extends TestCase
{
    public function testChangingVisibilityIsForbiddenInCommunityEdition(): void
    {
        /** @var User $user */
        $owner = User::factory()->admin()->create();

        Song::factory(3)->create();

        $this->putAs('api/songs/make-public', ['songs' => Song::query()->pluck('id')->all()], $owner)
            ->assertForbidden();

        $this->putAs('api/songs/make-private', ['songs' => Song::query()->pluck('id')->all()], $owner)
            ->assertForbidden();
    }
}
