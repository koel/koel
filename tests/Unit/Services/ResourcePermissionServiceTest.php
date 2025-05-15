<?php

namespace Tests\Unit\Services;

use App\Enums\PermissionableResourceType;
use App\Services\ResourcePermissionService;
use Illuminate\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ResourcePermissionServiceTest extends TestCase
{
    private Gate|MockInterface $gate;
    private ResourcePermissionService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->gate = Mockery::mock(Gate::class);
        $this->service = new ResourcePermissionService($this->gate);
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

        /** @var class-string<Model> $modelClass */
        $modelClass = $type->value;
        $subject = $modelClass::factory()->create(); // @phpstan-ignore-line

        $this->gate
            ->shouldReceive('forUser')
            ->with($user)
            ->andReturnSelf();

        $this->gate
            ->shouldReceive('allows')
            ->with('edit', Mockery::on(static fn (Model $s) => $s->is($subject)))
            ->andReturn(true);

        self::assertTrue($this->service->checkPermission($type, $subject->id, 'edit', $user));
    }
}
