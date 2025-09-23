<?php

namespace Tests\Unit\Services;

use App\Enums\Acl\Role;
use App\Enums\PermissionableResourceType;
use App\Models\Contracts\Permissionable;
use App\Services\Acl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;

class AclTest extends TestCase
{
    private Acl $acl;

    public function setUp(): void
    {
        parent::setUp();

        $this->acl = new Acl();
    }

    /**
     * @return array<array<PermissionableResourceType>>
     */
    public static function providePermissionAbleResourceTypes(): array
    {
        return [PermissionableResourceType::cases()];
    }

    #[Test]
    #[DataProvider('providePermissionAbleResourceTypes')]
    public function check(PermissionableResourceType $type): void
    {
        $user = create_user();

        /** @var class-string<Model|Permissionable> $modelClass */
        $modelClass = $type->modelClass();
        $subject = $modelClass::factory()->create(); // @phpstan-ignore-line

        Gate::expects('forUser')
            ->with($user)
            ->andReturnSelf();

        Gate::expects('allows')
            ->with('edit', Mockery::on(static fn (Model $s) => $s->is($subject)))
            ->andReturn(true);

        self::assertTrue($this->acl->checkPermission(
            $type,
            $subject->{$modelClass::getPermissionableIdentifier()}, // @phpstan-ignore-line
            'edit',
            $user
        ));
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
