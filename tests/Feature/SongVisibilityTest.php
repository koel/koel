<?php

namespace Tests\Feature;

use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class SongVisibilityTest extends TestCase
{
    #[Test]
    public function changingVisibilityIsForbiddenInCommunityEdition(): void
    {
        $owner = create_admin();
        Song::factory(3)->create();

        $this->putAs('api/songs/publicize', ['songs' => Song::query()->pluck('id')->all()], $owner)
            ->assertForbidden();

        $this->putAs('api/songs/privatize', ['songs' => Song::query()->pluck('id')->all()], $owner)
            ->assertForbidden();
    }
}
