<?php

namespace Tests\Unit\Models;

use App\Models\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class PersonalAccessTokenTest extends TestCase
{
    private PersonalAccessToken $token;

    public function setUp(): void
    {
        parent::setUp();

        create_user()->createToken('test');

        $this->token = PersonalAccessToken::query()->sole();
    }

    #[Test]
    public function skipRecentLastUsedAtUpdate(): void
    {
        $this->token->forceFill(['last_used_at' => now()->subSeconds(30)])->save();

        $this->token->forceFill(['last_used_at' => now()])->save();

        self::assertTrue($this->token->fresh()->last_used_at->isBefore(now()->subSeconds(29)));
    }

    #[Test]
    public function updateStaleLastUsedAt(): void
    {
        $this->token->forceFill(['last_used_at' => now()->subMinutes(2)])->save();

        $this->token->forceFill(['last_used_at' => now()])->save();

        self::assertTrue($this->token->fresh()->last_used_at->isAfter(now()->subSeconds(5)));
    }

    #[Test]
    public function setLastUsedAtForTheFirstTime(): void
    {
        $this->token->forceFill(['last_used_at' => now()])->save();

        self::assertNotNull($this->token->fresh()->last_used_at);
    }

    #[Test]
    public function saveOtherChangesAlongsideRecentLastUsedAt(): void
    {
        $this->token->forceFill(['last_used_at' => now()->subSeconds(30)])->save();

        $this->token->forceFill(['name' => 'renamed', 'last_used_at' => now()])->save();

        self::assertSame('renamed', $this->token->fresh()->name);
    }
}
