<?php

namespace Tests\Integration\Models;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function testSetUserPreferences(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->setPreference('foo', 'bar');

        self::assertSame('bar', $user->preferences['foo']);
    }

    public function testGetUserPreferences(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'preferences' => ['foo' => 'bar'],
        ]);

        self::assertEquals('bar', $user->getPreference('foo'));
    }

    public function testDeleteUserPreferences(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'preferences' => ['foo' => 'bar'],
        ]);
        $user->deletePreference('foo');

        self::assertArrayNotHasKey('foo', $user->preferences);
    }

    public function testSensitivePreferencesAreHidden(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'preferences' => ['lastfm_session_key' => 'foo'],
        ]);

        self::assertEquals('hidden', $user->preferences['lastfm_session_key']);
    }
}
