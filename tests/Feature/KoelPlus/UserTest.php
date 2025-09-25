<?php

namespace Tests\Feature\KoelPlus;

use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;

class UserTest extends PlusTestCase
{
    #[Test]
    public function creatingManagersIsOk(): void
    {
        $this->postAs('api/users', [
            'name' => 'Manager',
            'email' => 'foo@bar.com',
            'password' => 'secret',
            'role' => 'manager',
        ], create_admin())
            ->assertSuccessful();
    }

    #[Test]
    public function updatingUsersToManagersIsOk(): void
    {
        $user = create_admin();

        $this->putAs("api/users/{$user->public_id}", [
            'name' => 'Manager',
            'email' => 'foo@bar.com',
            'role' => 'manager',
        ], create_admin())
            ->assertSuccessful();
    }
}
