<?php

namespace Tests\Integration\Casts;

use App\Models\User;
use App\Values\UserPreferences;
use Tests\TestCase;

class UserPreferencesCastTest extends TestCase
{
    public function testCast(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'preferences' => [
                'lastfm_session_key' => 'foo',
            ],
        ]);

        self::assertInstanceOf(UserPreferences::class, $user->preferences);
        self::assertSame('foo', $user->preferences->lastFmSessionKey);

        $user->preferences->lastFmSessionKey = 'bar';
        $user->save();
        self::assertSame('bar', $user->refresh()->preferences->lastFmSessionKey);

        $user->preferences->lastFmSessionKey = null;
        $user->save();
        self::assertNull($user->refresh()->preferences->lastFmSessionKey);
    }
}
