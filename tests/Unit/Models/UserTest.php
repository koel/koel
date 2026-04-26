<?php

namespace Tests\Unit\Models;

use App\Enums\Acl\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;

class UserTest extends TestCase
{
    #[Test]
    public function adminCanAssignRolesTheyCanManage(): void
    {
        $admin = create_admin();

        $admin->getAssignableRoles()->each(static fn (Role $role) => self::assertTrue($admin->role->canManage($role)));
    }

    #[Test]
    public function userWithoutManageAbilityCannotAssignAnyRoles(): void
    {
        $user = create_user();

        self::assertTrue($user->getAssignableRoles()->isEmpty());
    }
}
