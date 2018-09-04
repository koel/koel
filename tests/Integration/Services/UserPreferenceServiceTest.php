<?php

namespace Tests\Integration\Services;

use App\Models\User;
use App\Services\UserPreferenceService;
use Tests\TestCase;

class UserPreferenceServiceTest extends TestCase
{
    /** @var UserPreferenceService */
    private $userPreferenceService;

    public function setUp()
    {
        parent::setUp();
        $this->userPreferenceService = new UserPreferenceService();
    }

    public function testGet(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create([
            'preferences' => ['foo' => 'bar'],
        ]);

        self::assertEquals('bar', $this->userPreferenceService->get($user, 'foo'));
    }

    public function testSet(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        $this->userPreferenceService->set($user, 'foo', 'bar');
        self::assertArraySubset(['foo' => 'bar'], $user->preferences);
    }

    public function testDelete(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create([
            'preferences' => ['foo' => 'bar'],
        ]);

        $this->userPreferenceService->delete($user, 'foo');
        self::assertArrayNotHasKey('foo', $user->preferences);
    }
}
