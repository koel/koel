<?php

namespace Tests\Feature;

use App\Helpers\Ulid;
use App\Models\Interaction;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;

class UserTest extends TestCase
{
    #[Test]
    public function nonAdminCannotCreateUser(): void
    {
        $this->postAs('api/user', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'secret',
            'is_admin' => false,
        ])->assertForbidden();
    }

    #[Test]
    public function adminCreatesUser(): void
    {
        $admin = create_admin();

        $this->postAs('api/user', [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'secret',
            'is_admin' => true,
        ], $admin)
            ->assertSuccessful();

        /** @var User $user */
        $user = User::query()->firstWhere('email', 'bar@baz.com');

        self::assertTrue(Hash::check('secret', $user->password));
        self::assertSame('Foo', $user->name);
        self::assertSame('bar@baz.com', $user->email);
        self::assertTrue($user->is_admin);
    }

    #[Test]
    public function adminUpdatesUser(): void
    {
        $admin = create_admin();
        $user = create_admin(['password' => 'secret']);

        $this->putAs("api/user/{$user->public_id}", [
            'name' => 'Foo',
            'email' => 'bar@baz.com',
            'password' => 'new-secret',
            'is_admin' => false,
        ], $admin)
            ->assertSuccessful();

        $user->refresh();

        self::assertTrue(Hash::check('new-secret', $user->password));
        self::assertSame('Foo', $user->name);
        self::assertSame('bar@baz.com', $user->email);
        self::assertFalse($user->is_admin);
    }

    #[Test]
    public function adminDeletesUser(): void
    {
        $user = create_user();

        $this->deleteAs("api/user/{$user->public_id}", [], create_admin());
        $this->assertModelMissing($user);
    }

    #[Test]
    public function selfDeletionNotAllowed(): void
    {
        $admin = create_admin();

        $this->deleteAs("api/user/{$admin->public_id}", [], $admin)->assertForbidden();
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
