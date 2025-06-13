<?php

namespace Tests\Unit\Services;

use App\Enums\PermissionableResourceType;
use App\Models\Contracts\PermissionableResource;
use App\Services\ResourcePermissionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ResourcePermissionServiceTest extends TestCase
{
    private ResourcePermissionService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = new ResourcePermissionService();
    }

    /**
     * @return array<mixed>
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

        /** @var class-string<Model|PermissionableResource> $modelClass */
        $modelClass = $type->value;
        $subject = $modelClass::factory()->create(); // @phpstan-ignore-line

        Gate::shouldReceive('forUser')
            ->with($user)
            ->andReturnSelf();

        Gate::shouldReceive('allows')
            ->with('edit', Mockery::on(static fn (Model $s) => $s->is($subject)))
            ->andReturn(true);

        self::assertTrue($this->service->checkPermission(
            $type,
            $subject->{$modelClass::getPermissionableResourceIdentifier()}, // @phpstan-ignore-line
            'edit',
            $user
        ));
    }
}
