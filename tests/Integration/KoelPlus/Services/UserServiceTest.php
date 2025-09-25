<?php

namespace Tests\Integration\KoelPlus\Services;

use App\Enums\Acl\Role;
use App\Models\User;
use App\Services\UserService;
use App\Values\User\SsoUser;
use App\Values\User\UserCreateData;
use App\Values\User\UserUpdateData;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class UserServiceTest extends PlusTestCase
{
    private UserService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(UserService::class);
    }

    #[Test]
    public function createUserViaSsoProvider(): void
    {
        $user = $this->service->createUser(UserCreateData::make(
            name: 'Bruce Dickinson',
            email: 'bruce@dickison.com',
            plainTextPassword: '',
            role: Role::ADMIN,
            avatar: 'https://lh3.googleusercontent.com/a/vatar',
            ssoId: '123',
            ssoProvider: 'Google'
        ));

        $this->assertModelExists($user);
        self::assertSame('Google', $user->sso_provider);
        self::assertSame('123', $user->sso_id);
        self::assertSame('https://lh3.googleusercontent.com/a/vatar', $user->avatar);
    }

    #[Test]
    public function createUserFromSso(): void
    {
        $this->assertDatabaseMissing(User::class, ['email' => 'bruce@iron.com']);

        $socialiteUser = Mockery::mock(SocialiteUser::class, [
            'getId' => '123',
            'getEmail' => 'bruce@iron.com',
            'getName' => 'Bruce Dickinson',
            'getAvatar' => 'https://lh3.googleusercontent.com/a/vatar',
        ]);

        $user = $this->service->createOrUpdateUserFromSso(SsoUser::fromSocialite($socialiteUser, 'Google'));

        $this->assertModelExists($user);

        self::assertSame('Google', $user->sso_provider);
        self::assertSame('Bruce Dickinson', $user->name);
        self::assertSame('bruce@iron.com', $user->email);
        self::assertSame('123', $user->sso_id);
        self::assertSame('https://lh3.googleusercontent.com/a/vatar', $user->avatar);
    }

    #[Test]
    public function updateUserFromSsoId(): void
    {
        $user = create_user([
            'email' => 'bruce@iron.com',
            'name' => 'Bruce Dickinson',
            'sso_id' => '123',
            'sso_provider' => 'Google',
        ]);

        $socialiteUser = Mockery::mock(SocialiteUser::class, [
            'getId' => '123',
            'getEmail' => 'steve@iron.com',
            'getName' => 'Steve Harris',
            'getAvatar' => 'https://lh3.googleusercontent.com/a/vatar',
        ]);

        $this->service->createOrUpdateUserFromSso(SsoUser::fromSocialite($socialiteUser, 'Google'));
        $user->refresh();

        self::assertSame('Bruce Dickinson', $user->name); // Name should not be updated
        self::assertSame('https://lh3.googleusercontent.com/a/vatar', $user->avatar);
        self::assertSame('bruce@iron.com', $user->email); // Email should not be updated
        self::assertSame('Google', $user->sso_provider);
    }

    #[Test]
    public function updateUserFromSsoEmail(): void
    {
        $user = create_user([
            'email' => 'bruce@iron.com',
            'name' => 'Bruce Dickinson',
        ]);

        $socialiteUser = Mockery::mock(SocialiteUser::class, [
            'getId' => '123',
            'getEmail' => 'bruce@iron.com',
            'getName' => 'Steve Harris',
            'getAvatar' => 'https://lh3.googleusercontent.com/a/vatar',
        ]);

        $this->service->createOrUpdateUserFromSso(SsoUser::fromSocialite($socialiteUser, 'Google'));
        $user->refresh();

        self::assertSame('Bruce Dickinson', $user->name); // Name should not be updated
        self::assertSame('https://lh3.googleusercontent.com/a/vatar', $user->avatar);
        self::assertSame('Google', $user->sso_provider);
    }

    #[Test]
    public function updateSsoUserCannotChangeProfileDetails(): void
    {
        $user = create_user([
            'email' => 'bruce@iron.com',
            'name' => 'Bruce Dickinson',
            'sso_provider' => 'Google',
        ]);

        $this->service->updateUser($user, UserUpdateData::make(
            name: 'Steve Harris',
            email: 'steve@iron.com',
            plainTextPassword: 'TheTrooper',
            role: Role::MANAGER,
        ));

        $user->refresh();

        self::assertSame('bruce@iron.com', $user->email);
        self::assertFalse(Hash::check('TheTrooper', $user->password));
        self::assertSame(Role::MANAGER, $user->role);
    }
}
