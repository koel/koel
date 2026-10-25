<?php

namespace Tests\Feature;

use App\Enums\Acl\Role;
use App\Facades\Dispatcher;
use App\Helpers\Ulid;
use App\Jobs\DeleteUserJob;
use App\Models\Interaction;
use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\TokenManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_manager;
use function Tests\create_user;

class UserTest extends TestCase
{
    #[Test]
    public function nonAdminCannotCreateUser(): void
    {
        $this->postAs('api/users', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'secret',
            'role' => 'user',
        ])->assertForbidden();
    }

    #[Test]
    public function adminCreatesUser(): void
    {
        $this->postAs(
            'api/users',
            [
                'name' => 'Foo',
                'email' => 'bar@baz.com',
                'password' => 'secret',
                'role' => 'admin',
            ],
            create_admin(),
        )->assertSuccessful();

        /** @var User $user */
        $user = User::query()->firstWhere('email', 'bar@baz.com');

        self::assertTrue(Hash::check('secret', $user->password));
        self::assertSame('Foo', $user->name);
        self::assertSame('bar@baz.com', $user->email);
        self::assertSame(Role::ADMIN, $user->role);
    }

    #[Test]
    public function userWithNonAvailableRoleCannotBeCreated(): void
    {
        $this
            ->postAs(
                'api/users',
                [
                    'name' => 'Foo',
                    'email' => 'bar@baz.com',
                    'password' => 'secret',
                    'role' => 'manager',
                ],
                create_admin(),
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    #[Test]
    public function privilegeEscalationIsForbiddenWhenCreating(): void
    {
        $this
            ->postAs(
                'api/users',
                [
                    'name' => 'Foo',
                    'email' => 'bar@baz.com',
                    'password' => 'secret',
                    'role' => 'admin',
                ],
                create_manager(),
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    #[Test]
    public function creatingUsersWithHigherRoleIsNotAllowed(): void
    {
        $admin = create_admin();

        $this->putAs(
            "api/users/{$admin->public_id}",
            [
                'name' => 'Foo',
                'email' => 'bar@baz.com',
                'password' => 'new-secret',
                'role' => 'user',
            ],
            create_manager(),
        )->assertForbidden();
    }

    #[Test]
    public function adminUpdatesUser(): void
    {
        $admin = create_admin();
        $user = create_admin(['password' => 'secret']);

        $this->putAs(
            "api/users/{$user->public_id}",
            [
                'name' => 'Foo',
                'email' => 'bar@baz.com',
                'password' => 'new-secret',
                'role' => 'user',
            ],
            $admin,
        )->assertSuccessful();

        $user->refresh();

        self::assertTrue(Hash::check('new-secret', $user->password));
        self::assertSame('Foo', $user->name);
        self::assertSame('bar@baz.com', $user->email);
        self::assertSame(Role::USER, $user->role);
    }

    #[Test]
    public function adminUpdatesUserKeepingAvatar(): void
    {
        $user = create_user(['avatar' => 'foo.jpg']);

        $this->putAs(
            "api/users/{$user->public_id}",
            [
                'name' => 'Foo',
                'email' => 'bar@baz.com',
                'role' => 'user',
            ],
            create_admin(),
        )->assertSuccessful();

        $user->refresh();

        self::assertSame('foo.jpg', $user->getRawOriginal('avatar'));
    }

    #[Test]
    public function privilegeEscalationIsForbiddenWhenUpdating(): void
    {
        $manager = create_manager();

        $this
            ->putAs(
                "api/users/{$manager->public_id}",
                [
                    'role' => 'admin',
                ],
                create_manager(),
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    #[Test]
    public function updatingUserToANonAvailableRoleIsNotAllowed(): void
    {
        $manager = create_manager();

        $this
            ->putAs(
                "api/users/{$manager->public_id}",
                [
                    'role' => 'manager',
                ],
                create_manager(),
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    #[Test]
    public function adminDeletesUser(): void
    {
        $user = create_user();

        $this->deleteAs("api/users/{$user->public_id}", [], create_admin());
        $this->assertModelMissing($user);
    }

    #[Test]
    public function deletedUserIsLockedOutBeforeTheDeletionJobRuns(): void
    {
        Dispatcher::expects('dispatch')->with(Mockery::type(DeleteUserJob::class));

        $user = create_user();
        $token = app(TokenManager::class)->createCompositeToken($user);

        $this->deleteAs("api/users/{$user->public_id}", [], create_admin())->assertNoContent();

        $this->app['auth']->forgetGuards();

        $this->withToken($token->apiToken)->getJson('api/data')->assertUnauthorized();
        $this->get("download/songs?api_token={$token->audioToken}")->assertUnauthorized();
        $this->assertModelExists($user);
    }

    #[Test]
    public function selfUpdateNotAllowed(): void
    {
        $admin = create_admin();

        $this->putAs(
            "api/users/{$admin->public_id}",
            [
                'name' => 'Foo',
                'email' => 'bar@baz.com',
                'role' => 'user',
            ],
            $admin,
        )->assertForbidden();

        self::assertSame(Role::ADMIN, $admin->refresh()->role);
    }

    #[Test]
    public function selfDeletionNotAllowed(): void
    {
        $admin = create_admin();

        $this->deleteAs("api/users/{$admin->public_id}", [], $admin)->assertForbidden();
        $this->assertModelExists($admin);
    }

    #[Test]
    public function pruneOldDemoAccounts(): void
    {
        config(['koel.misc.demo' => true]);

        $oldUserWithNoActivity = create_user([
            'created_at' => now()->subDays(30),
            'email' => Ulid::generate() . '@demo.koel.dev',
        ]);

        $oldUserWithOldActivity = create_user([
            'created_at' => now()->subDays(30),
            'email' => Ulid::generate() . '@demo.koel.dev',
        ]);

        Interaction::factory()->for($oldUserWithOldActivity)->createOne([
            'last_played_at' => now()->subDays(14),
        ]);

        $oldUserWithNonDemoEmail = create_user([
            'created_at' => now()->subDays(30),
            'email' => Ulid::generate() . '@example.com',
        ]);

        $oldUserWithNewActivity = create_user([
            'created_at' => now()->subDays(30),
            'email' => Ulid::generate() . '@demo.koel.dev',
        ]);

        Interaction::factory()->for($oldUserWithNewActivity)->createOne([
            'last_played_at' => now()->subDays(6),
        ]);

        $newUser = create_user([
            'created_at' => now()->subDay(),
            'email' => Ulid::generate() . '@demo.koel.dev',
        ]);

        Artisan::call('model:prune');

        $this->assertModelMissing($oldUserWithNoActivity);
        $this->assertModelMissing($oldUserWithOldActivity);
        $this->assertModelExists($oldUserWithNonDemoEmail);
        $this->assertModelExists($oldUserWithNewActivity);
        $this->assertModelExists($newUser);

        config(['koel.misc.demo' => false]);
    }

    #[Test]
    public function pruneInvitationsOlderThanAWeek(): void
    {
        $expired = User::factory()
            ->prospect()
            ->createOne(['invited_at' => now()->subDays(8)]);
        $pending = User::factory()
            ->prospect()
            ->createOne(['invited_at' => now()->subDays(6)]);
        $member = create_user(['created_at' => now()->subDays(30)]);

        Artisan::call('model:prune');

        $this->assertModelMissing($expired);
        $this->assertModelExists($pending);
        $this->assertModelExists($member);
    }

    #[Test]
    public function noPruneIfNotInDemoMode(): void
    {
        $user = create_user([
            'created_at' => now()->subDays(30),
            'email' => Ulid::generate() . '@demo.koel.dev',
        ]);

        Artisan::call('model:prune');

        $this->assertModelExists($user);
    }

    #[Test]
    public function listingIncludesEditAndDeletePermissionsForAdminViewingLowerRoleUser(): void
    {
        $admin = create_admin();
        $regular = create_user();

        $response = $this->getAs('api/users', $admin)->json();
        $regularRow = collect($response)->firstWhere('id', $regular->public_id);

        self::assertTrue($regularRow['permissions']['edit']);
        self::assertTrue($regularRow['permissions']['delete']);
    }

    #[Test]
    public function listingDeniesEditAndDeletePermissionsForSelf(): void
    {
        $admin = create_admin();

        $response = $this->getAs('api/users', $admin)->json();
        $selfRow = collect($response)->firstWhere('id', $admin->public_id);

        self::assertFalse($selfRow['permissions']['edit']);
        self::assertFalse($selfRow['permissions']['delete']);
    }

    #[Test]
    public function listingExcludesUsersOfAnotherOrganization(): void
    {
        $admin = create_admin();
        $colleague = create_user();
        $outsider = self::createUserOfAnotherOrganization();

        $this
            ->getAs('api/users', $admin)
            ->assertSuccessful()
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $colleague->public_id])
            ->assertJsonMissing(['id' => $outsider->public_id]);
    }

    #[Test]
    public function adminCannotUpdateUserOfAnotherOrganization(): void
    {
        $outsider = self::createUserOfAnotherOrganization();
        $originalName = $outsider->name;

        $this->putAs(
            "api/users/{$outsider->public_id}",
            [
                'name' => 'Foo',
                'email' => $outsider->email,
                'role' => 'admin',
            ],
            create_admin(),
        )->assertForbidden();

        self::assertSame($originalName, $outsider->refresh()->name);
    }

    #[Test]
    public function adminCannotDeleteUserOfAnotherOrganization(): void
    {
        $outsider = self::createUserOfAnotherOrganization();

        $this->deleteAs("api/users/{$outsider->public_id}", [], create_admin())->assertForbidden();

        $this->assertModelExists($outsider);
    }

    private static function createUserOfAnotherOrganization(): User
    {
        return User::factory()->for(Organization::factory()->createOne())->createOne();
    }
}
