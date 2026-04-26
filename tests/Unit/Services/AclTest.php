<?php

namespace Tests\Unit\Services;

use App\Enums\Acl\Role;
use App\Services\Acl;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class AclTest extends TestCase
{
    private Acl $acl;

    public function setUp(): void
    {
        parent::setUp();

        $this->acl = new Acl();
    }

    #[Test]
    public function getAssignableRolesForUser(): void
    {
        $admin = create_admin();

        $this->acl
            ->getAssignableRolesForUser($admin)
            ->each(static fn (Role $role) => self::assertTrue($admin->role->canManage($role)));
    }
}
