<?php

namespace Tests\Unit\KoelPlus\Models;

use App\Enums\Acl\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_manager;

class UserTest extends PlusTestCase
{
    /**
     * In Plus, Role::available() admits MANAGER and GUEST. The admin's canManage() permits every
     * role, so getAssignableRoles() returns all four ordered by level() ascending.
     */
    #[Test]
    public function adminCanAssignAllRolesOrderedByLevel(): void
    {
        $admin = create_admin();

        self::assertSame([Role::GUEST, Role::USER, Role::MANAGER, Role::ADMIN], $admin->getAssignableRoles()->all());
    }

    /**
     * In Plus, MANAGER passes Role::available() and the manager satisfies canManage(MANAGER)
     * since 2 >= 2; only ADMIN is filtered out by canManage() (level 3 > manager's 2). Net
     * result of getAssignableRoles() is [GUEST, USER, MANAGER].
     */
    #[Test]
    public function managerCannotAssignAdminRole(): void
    {
        $manager = create_manager();

        self::assertSame([Role::GUEST, Role::USER, Role::MANAGER], $manager->getAssignableRoles()->all());
    }
}
