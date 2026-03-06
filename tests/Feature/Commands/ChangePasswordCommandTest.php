<?php

namespace Tests\Feature\Commands;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class ChangePasswordCommandTest extends TestCase
{
    #[Test]
    public function changePasswordForDefaultAdmin(): void
    {
        $admin = create_admin();

        $this
            ->artisan('koel:admin:change-password')
            ->expectsQuestion('Your desired password', 'new-password')
            ->expectsQuestion('Again, just to be sure', 'new-password')
            ->assertSuccessful();

        $admin->refresh();
        self::assertTrue(Hash::check('new-password', $admin->password));
    }

    #[Test]
    public function changePasswordForSpecificUser(): void
    {
        $user = User::factory()->create(['email' => 'john@example.com']);

        $this
            ->artisan('koel:admin:change-password', ['email' => 'john@example.com'])
            ->expectsQuestion('Your desired password', 'secret123')
            ->expectsQuestion('Again, just to be sure', 'secret123')
            ->assertSuccessful();

        $user->refresh();
        self::assertTrue(Hash::check('secret123', $user->password));
    }

    #[Test]
    public function failForNonExistentUser(): void
    {
        $this
            ->artisan('koel:admin:change-password', ['email' => 'nobody@example.com'])
            ->expectsOutput('The user account cannot be found.')
            ->assertFailed();
    }
}
