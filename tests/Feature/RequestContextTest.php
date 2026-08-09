<?php

namespace Tests\Feature;

use App\Helpers\Uuid;
use Illuminate\Support\Facades\Context;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class RequestContextTest extends TestCase
{
    #[Test]
    public function tagsRequestsWithAnId(): void
    {
        $uuid = Uuid::freeze();

        $this->get('api/ping')->assertOk();

        self::assertSame($uuid, Context::get('request_id'));
    }

    #[Test]
    public function tagsRequestsWithTheAuthenticatedUser(): void
    {
        $user = create_user();

        $this->getAs('api/me', $user)->assertOk();

        self::assertSame($user->id, Context::get('user_id'));
    }

    #[Test]
    public function doesNotTagGuestRequestsWithAUser(): void
    {
        $this->get('api/ping')->assertOk();

        self::assertNull(Context::get('user_id'));
    }
}
