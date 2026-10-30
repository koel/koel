<?php

namespace Tests\Unit\Policies;

use App\Policies\UserPolicy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class UserPolicyTest extends TestCase
{
    #[Test]
    public function cannotEmbedAnotherUsersFavorites(): void
    {
        self::assertFalse((new UserPolicy())->embed(create_user(), create_user()));
    }
}
