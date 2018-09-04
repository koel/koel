<?php

namespace Tests\Integration\Models;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    /** @test */
    public function sensitive_preferences_are_hidden()
    {
        /** @var User $user */
        $user = factory(User::class)->create([
            'preferences' => ['lastfm_session_key' => 'foo'],
        ]);

        $value = $user->preferences['lastfm_session_key'];
        self::assertEquals('hidden', $value);
    }
}
