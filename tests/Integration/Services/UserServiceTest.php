<?php

namespace Tests\Integration\Services;

use App\Enums\Acl\Role;
use App\Events\UserUnsubscribedFromPodcast;
use App\Exceptions\UserProspectUpdateDeniedException;
use App\Facades\Dispatcher;
use App\Jobs\DeleteSongFilesJob;
use App\Models\Organization;
use App\Models\Podcast;
use App\Models\RadioStation;
use App\Models\Song;
use App\Services\UserService;
use App\Values\User\AvatarUpdateData;
use App\Values\User\UserCreateData;
use App\Values\User\UserUpdateData;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_playlist;
use function Tests\create_playlists;
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

    #[Test]
    public function deleteUserWithTheirSongsAndFiles(): void
    {
        $user = create_user();
        $ownSongs = Song::factory()->for($user, 'owner')->createMany(2);
        $otherSong = Song::factory()->createOne();

        Dispatcher::expects('dispatch')->with(Mockery::on(
            static fn (DeleteSongFilesJob $job): bool => (
                $job->files->pluck('location')->sort()->values()->all() === $ownSongs
                    ->pluck('path')
                    ->sort()
                    ->values()
                    ->all()
            ),
        ));

        $this->service->deleteUser($user);

        $this->assertModelMissing($user);
        $ownSongs->each($this->assertModelMissing(...));
        $this->assertModelExists($otherSong);
    }

    #[Test]
    public function deleteUserWithTheirPlaylistsAndRadioStations(): void
    {
        $user = create_user();
        $ownPlaylist = create_playlists(1, owner: $user)->first();
        $otherPlaylist = create_playlist();
        $ownStation = RadioStation::factory()->for($user)->createOne();

        $this->service->deleteUser($user);

        $this->assertModelMissing($ownPlaylist);
        $this->assertModelExists($otherPlaylist);
        $this->assertModelMissing($ownStation);
    }

    #[Test]
    public function unsubscribeADeletedUserFromTheirPodcasts(): void
    {
        $user = create_user();
        $podcast = Podcast::factory()->createOne();
        $user->podcasts()->attach($podcast);

        Event::fake(UserUnsubscribedFromPodcast::class);

        $this->service->deleteUser($user);

        Event::assertDispatched(UserUnsubscribedFromPodcast::class, static fn (UserUnsubscribedFromPodcast $event): bool => $event->podcast->is(
            $podcast,
        ));
    }
}
