<?php

namespace Tests\Integration\Services;

use App\Exceptions\KoelPlusRequiredException;
use App\Exceptions\UserProspectUpdateDeniedException;
use App\Services\UserService;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;
use function Tests\create_user_prospect;
use function Tests\read_as_data_url;
use function Tests\test_path;

class UserServiceTest extends TestCase
{
    private UserService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(UserService::class);
    }

    #[Test]
    public function createUser(): void
    {
        $user = $this->service->createUser(
            name: 'Bruce Dickinson',
            email: 'bruce@dickison.com',
            plainTextPassword: 'FearOfTheDark',
            isAdmin: true,
            avatar: read_as_data_url(test_path('blobs/cover.png')),
        );

        self::assertModelExists($user);
        self::assertTrue(Hash::check('FearOfTheDark', $user->password));
        self::assertTrue($user->is_admin);
        self::assertFileExists(user_avatar_path($user->getRawOriginal('avatar')));
    }

    #[Test]
    public function createUserWithEmptyAvatarHasGravatar(): void
    {
        $user = $this->service->createUser(
            name: 'Bruce Dickinson',
            email: 'bruce@dickison.com',
            plainTextPassword: 'FearOfTheDark',
            isAdmin: false
        );

        self::assertModelExists($user);
        self::assertTrue(Hash::check('FearOfTheDark', $user->password));
        self::assertFalse($user->is_admin);
        self::assertStringStartsWith('https://www.gravatar.com/avatar/', $user->avatar);
    }

    #[Test]
    public function createUserWithNoPassword(): void
    {
        $user = $this->service->createUser(
            name: 'Bruce Dickinson',
            email: 'bruce@dickison.com',
            plainTextPassword: '',
            isAdmin: false
        );

        self::assertModelExists($user);
        self::assertEmpty($user->password);
    }

    #[Test]
    public function createSSOUserRequiresKoelPlus(): void
    {
        $this->expectException(KoelPlusRequiredException::class);

        $this->service->createUser(
            name: 'Bruce Dickinson',
            email: 'bruce@dickison.com',
            plainTextPassword: 'FearOfTheDark',
            isAdmin: false,
            ssoProvider: 'Google'
        );
    }

    #[Test]
    public function updateUser(): void
    {
        $user = create_user();

        $this->service->updateUser(
            user: $user,
            name: 'Steve Harris',
            email: 'steve@iron.com',
            password: 'TheTrooper',
            isAdmin: true,
            avatar: read_as_data_url(test_path('blobs/cover.png'))
        );

        $user->refresh();

        self::assertSame('Steve Harris', $user->name);
        self::assertSame('steve@iron.com', $user->email);
        self::assertTrue(Hash::check('TheTrooper', $user->password));
        self::assertTrue($user->is_admin);
        self::assertFileExists(user_avatar_path($user->getRawOriginal('avatar')));
    }

    #[Test]
    public function updateUserWithoutSettingPasswordOrAdminStatus(): void
    {
        $user = create_admin(['password' => Hash::make('TheTrooper')]);

        $this->service->updateUser(
            user: $user,
            name: 'Steve Harris',
            email: 'steve@iron.com'
        );

        $user->refresh();

        self::assertSame('Steve Harris', $user->name);
        self::assertSame('steve@iron.com', $user->email);
        self::assertTrue(Hash::check('TheTrooper', $user->password));
        self::assertTrue($user->is_admin);
    }

    #[Test]
    public function updateProspectUserIsNotAllowed(): void
    {
        $this->expectException(UserProspectUpdateDeniedException::class);

        $this->service->updateUser(
            user: create_user_prospect(),
            name: 'Steve Harris',
            email: 'steve@iron.com'
        );
    }
}
