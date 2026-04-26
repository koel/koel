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
    #[Test]
    public function adminCanAssignAvailableRolesOrderedByLevel(): void
    {
        $admin = create_admin();

        // In CE, MANAGER is not available (Plus-only); the admin can manage USER and ADMIN,
        // ordered by level() ascending.
        self::assertSame([Role::USER, Role::ADMIN], $admin->getAssignableRoles()->all());
    }

    #[Test]
    public function managerCannotAssignRolesAboveTheirLevel(): void
    {
        $manager = create_manager();

        // canManage() filter excludes ADMIN (level 3 > manager's level 2); MANAGER itself is
        // also filtered out by Role::available() in CE. Net result: only USER.
        self::assertSame([Role::USER], $manager->getAssignableRoles()->all());
    }

    #[Test]
    public function userWithoutManageAbilityCannotAssignAnyRoles(): void
    {
        $user = create_user();

        self::assertSame([], $user->getAssignableRoles()->all());
    }
}
