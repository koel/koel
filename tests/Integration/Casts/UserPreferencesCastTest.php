<?php

namespace Tests\Integration\Casts;

use App\Values\UserPreferences;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class UserPreferencesCastTest extends TestCase
{
    #[Test]
    public function cast(): void
    {
        $user = create_user([
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
