<?php

namespace Tests\Integration\Services;

use App\Enums\Acl\Role;
use App\Exceptions\UserProspectUpdateDeniedException;
use App\Models\Organization;
use App\Services\UserService;
use App\Values\User\AvatarUpdateData;
use App\Values\User\UserCreateData;
use App\Values\User\UserUpdateData;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;
use function Tests\create_user_prospect;
use function Tests\minimal_base64_encoded_image;

class UserServiceTest extends TestCase
{
    private UserService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(UserService::class);
    }

    #[Test]
    public function theFirstUserOwnsTheOrganization(): void
    {
        $organization = Organization::factory()->createOne();
        self::assertNull($organization->owner_id);

        $owner = $this->service->createUser(
            UserCreateData::make(
                name: 'Bruce Dickinson',
                email: 'bruce@dickinson.test',
                plainTextPassword: 'FearOfTheDark',
                role: Role::ADMIN,
            ),
            $organization,
        );

        self::assertTrue($organization->refresh()->owner->is($owner));
    }

    #[Test]
    public function laterUsersDoNotTakeOverTheOrganization(): void
    {
        $organization = Organization::factory()->createOne();

        $owner = $this->service->createUser(
            UserCreateData::make(
                name: 'Bruce Dickinson',
                email: 'bruce@dickinson.test',
                plainTextPassword: 'FearOfTheDark',
                role: Role::ADMIN,
            ),
            $organization,
        );

        $this->service->createUser(
            UserCreateData::make(
                name: 'Steve Harris',
                email: 'steve@harris.test',
                plainTextPassword: 'TheTrooper',
                role: Role::USER,
            ),
            $organization,
        );

        self::assertTrue($organization->refresh()->owner->is($owner));
    }

    #[Test]
    public function aStaleOrganizationCannotStealOwnership(): void
    {
        $organization = Organization::factory()->createOne();

        $owner = $this->service->createUser(
            UserCreateData::make(
                name: 'Bruce Dickinson',
                email: 'bruce@dickinson.test',
                plainTextPassword: 'FearOfTheDark',
                role: Role::ADMIN,
            ),
            $organization,
        );

        // A copy loaded before the owner was set, as a concurrent request would hold.
        $stale = Organization::query()->whereKey($organization->getKey())->first();
        $stale->owner_id = null;

        $this->service->createUser(
            UserCreateData::make(
                name: 'Steve Harris',
                email: 'steve@harris.test',
                plainTextPassword: 'TheTrooper',
                role: Role::USER,
            ),
            $stale,
        );

        self::assertTrue($organization->refresh()->owner->is($owner));
    }

    #[Test]
    public function createUser(): void
    {
        $user = $this->service->createUser(UserCreateData::make(
            name: 'Bruce Dickinson',
            email: 'bruce@dickison.com',
            plainTextPassword: 'FearOfTheDark',
            role: Role::ADMIN,
            avatar: minimal_base64_encoded_image(),
        ));

        $this->assertModelExists($user);
        self::assertTrue(Hash::check('FearOfTheDark', $user->password));
        self::assertSame(Role::ADMIN, $user->role);
        self::assertFileExists(image_storage_path($user->getRawOriginal('avatar')));
    }

    #[Test]
    public function createUserWithEmptyAvatarHasGravatar(): void
    {
        $user = $this->service->createUser(UserCreateData::make(
            name: 'Bruce Dickinson',
            email: 'bruce@dickison.com',
            plainTextPassword: 'FearOfTheDark',
        ));

        $this->assertModelExists($user);
        self::assertTrue(Hash::check('FearOfTheDark', $user->password));
        self::assertSame(Role::USER, $user->role);
        self::assertStringStartsWith('https://www.gravatar.com/avatar/', $user->avatar);
    }

    #[Test]
    public function createUserWithNoPassword(): void
    {
        $user = $this->service->createUser(UserCreateData::make(
            name: 'Bruce Dickinson',
            email: 'bruce@dickison.com',
            plainTextPassword: '',
        ));

        $this->assertModelExists($user);
        self::assertEmpty($user->password);
    }

    #[Test]
    public function updateUser(): void
    {
        $user = create_user();

        $this->service->updateUser($user, UserUpdateData::make(
            name: 'Steve Harris',
            email: 'steve@iron.com',
            plainTextPassword: 'TheTrooper',
            role: Role::ADMIN,
            avatar: AvatarUpdateData::make(minimal_base64_encoded_image()),
        ));

        $user->refresh();

        self::assertSame('Steve Harris', $user->name);
        self::assertSame('steve@iron.com', $user->email);
        self::assertTrue(Hash::check('TheTrooper', $user->password));
        self::assertSame(Role::ADMIN, $user->role);
        self::assertFileExists(image_storage_path($user->getRawOriginal('avatar')));
    }

    #[Test]
    public function updateUserKeepingAvatar(): void
    {
        $user = create_user(['avatar' => 'foo.jpg']);

        $this->service->updateUser($user, UserUpdateData::make(name: 'Steve Harris', email: 'steve@iron.com'));

        self::assertSame('foo.jpg', $user->refresh()->getRawOriginal('avatar'));
    }

    #[Test]
    public function updateUserRemovingAvatar(): void
    {
        $user = create_user(['avatar' => 'foo.jpg']);

        $this->service->updateUser($user, UserUpdateData::make(
            name: 'Steve Harris',
            email: 'steve@iron.com',
            avatar: AvatarUpdateData::make(),
        ));

        self::assertNull($user->refresh()->getRawOriginal('avatar'));
    }

    #[Test]
    public function updateUserWithoutSettingPasswordOrRole(): void
    {
        $user = create_admin(['password' => Hash::make('TheTrooper')]);
        self::assertSame(Role::ADMIN, $user->role);

        $this->service->updateUser($user, UserUpdateData::make(name: 'Steve Harris', email: 'steve@iron.com'));

        $user->refresh();

        self::assertSame('Steve Harris', $user->name);
        self::assertSame('steve@iron.com', $user->email);
        self::assertTrue(Hash::check('TheTrooper', $user->password));
        self::assertSame(Role::ADMIN, $user->role); // shouldn't change
    }

    #[Test]
    public function updateProspectUserIsNotAllowed(): void
    {
        $this->expectException(UserProspectUpdateDeniedException::class);

        $this->service->updateUser(create_user_prospect(), UserUpdateData::make(
            name: 'Steve Harris',
            email: 'steve@iron.com',
        ));
    }
}
