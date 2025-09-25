<?php

namespace Tests\Feature;

use App\Enums\Acl\Role;
use App\Helpers\Ulid;
use App\Models\Interaction;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
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
        $this->postAs('api/users', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'secret',
            'role' => 'admin',
        ], create_admin())
            ->assertSuccessful();

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
        $this->postAs('api/users', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'secret',
            'role' => 'manager',
        ], create_admin())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    #[Test]
    public function privilegeEscalationIsForbiddenWhenCreating(): void
    {
        $this->postAs('api/users', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'secret',
            'role' => 'admin',
        ], create_manager())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    #[Test]
    public function creatingUsersWithHigherRoleIsNotAllowed(): void
    {
        $admin = create_admin();

        $this->putAs("api/users/{$admin->public_id}", [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'new-secret',
            'role' => 'user',
        ], create_manager())
            ->assertForbidden();
    }

    #[Test]
    public function adminUpdatesUser(): void
    {
        $admin = create_admin();
        $user = create_admin(['password' => 'secret']);

        $this->putAs("api/users/{$user->public_id}", [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'new-secret',
            'role' => 'user',
        ], $admin)
            ->assertSuccessful();

        $user->refresh();

        self::assertTrue(Hash::check('new-secret', $user->password));
        self::assertSame('Foo', $user->name);
        self::assertSame('bar@baz.com', $user->email);
        self::assertSame(Role::USER, $user->role);
    }

    #[Test]
    public function privilegeEscalationIsForbiddenWhenUpdating(): void
    {
        $manager = create_manager();

        $this->putAs("api/users/{$manager->public_id}", [
            'role' => 'admin',
        ], create_manager())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    #[Test]
    public function updatingUserToANonAvailableRoleIsNotAllowed(): void
    {
        $manager = create_manager();

        $this->putAs("api/users/{$manager->public_id}", [
            'role' => 'manager',
        ], create_manager())
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

        Interaction::factory()->for($oldUserWithOldActivity)->create([
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

        Interaction::factory()->for($oldUserWithNewActivity)->create([
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
    public function noPruneIfNotInDemoMode(): void
    {
        $user = create_user([
            'created_at' => now()->subDays(30),
            'email' => Ulid::generate() . '@demo.koel.dev',
        ]);

        Artisan::call('model:prune');

        $this->assertModelExists($user);
    }
}
