<?php

namespace Tests\Integration\Models;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    /** @test */
    public function user_preferences_can_be_set()
    {
        // Given a user
        /** @var User $user */
        $user = factory(User::class)->create();

        // When I see the user's preference
        $user->setPreference('foo', 'bar');

        // Then I see the preference properly set
        $this->assertArraySubset(['foo' => 'bar'], $user->preferences);
    }

    /** @test */
    public function user_preferences_can_be_retrieved()
    {
        // Given a user with some preferences
        /** @var User $user */
        $user = factory(User::class)->create([
            'preferences' => ['foo' => 'bar'],
        ]);

        // When I get a preference by its key
        $value = $user->getPreference('foo');

        // Then I retrieve the preference value
        $this->assertEquals('bar', $value);
    }

    /** @test */
    public function user_preferences_can_be_deleted()
    {
        // Given a user with some preferences
        /** @var User $user */
        $user = factory(User::class)->create([
            'preferences' => ['foo' => 'bar'],
        ]);

        // When I delete the preference by its key
        $user->deletePreference('foo');

        // Then I see the preference gets deleted
        $this->assertArrayNotHasKey('foo', $user->preferences);
    }

    /** @test */
    public function sensitive_preferences_are_hidden()
    {
        // Given a user with sensitive preferences
        /** @var User $user */
        $user = factory(User::class)->create([
            'preferences' => ['lastfm_session_key' => 'foo'],
        ]);

        // When I try to access the sensitive preferences
        $value = $user->preferences['lastfm_session_key'];

        // Then the sensitive preferences are replaced with "hidden"
        $this->assertEquals('hidden', $value);
    }
}
