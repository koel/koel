<?php

namespace Tests\Unit\KoelPlus\Models;

use App\Enums\Acl\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_manager;

class UserTest extends PlusTestCase
{
    #[Test]
    public function adminCanAssignAllRolesOrderedByLevel(): void
    {
        $admin = create_admin();

        // In Plus, MANAGER is available; admin can manage every role. Ordered by level().
        self::assertSame([Role::USER, Role::MANAGER, Role::ADMIN], $admin->getAssignableRoles()->all());
    }

    #[Test]
    public function managerCannotAssignAdminRole(): void
    {
        $manager = create_manager();

        // canManage() filter excludes ADMIN (level 3 > manager's level 2); MANAGER itself is
        // available in Plus and the manager can canManage(MANAGER) = true (2 >= 2).
        self::assertSame([Role::USER, Role::MANAGER], $manager->getAssignableRoles()->all());
    }
}
