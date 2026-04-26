<?php

namespace Tests\Unit\Models;

use App\Enums\Acl\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_manager;
use function Tests\create_user;

class UserTest extends TestCase
{
    /**
     * In CE, MANAGER is filtered out by Role::available() (Plus-only). The admin can manage
     * USER and ADMIN via canManage(), and getAssignableRoles() returns them ordered by level()
     * ascending — hence [USER, ADMIN].
     */
    #[Test]
    public function adminCanAssignAvailableRolesOrderedByLevel(): void
    {
        $admin = create_admin();

        self::assertSame([Role::USER, Role::ADMIN], $admin->getAssignableRoles()->all());
    }

    /**
     * Two filters apply for a manager (created via create_manager()): canManage() excludes
     * ADMIN (level 3 > manager's level 2), and Role::available() also filters out MANAGER
     * itself in CE. Net result of getAssignableRoles() is [USER] only.
     */
    #[Test]
    public function managerCannotAssignRolesAboveTheirLevel(): void
    {
        $manager = create_manager();

        self::assertSame([Role::USER], $manager->getAssignableRoles()->all());
    }

    #[Test]
    public function userWithoutManageAbilityCannotAssignAnyRoles(): void
    {
        $user = create_user();

        self::assertSame([], $user->getAssignableRoles()->all());
    }
}
