<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\Agents\KoelAssistant;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class AiControllerTest extends PlusTestCase
{
    #[Test]
    public function promptsTheAgent(): void
    {
        KoelAssistant::fake(['Hello, how can I help you with your music?']);

        $user = create_user();

        $this
            ->postAs(
                'api/ai/prompt',
                [
                    'prompt' => 'Play some jazz',
                ],
                $user,
            )
            ->assertSuccessful()
            ->assertJsonStructure(['message', 'action', 'data', 'conversation_id']);

        KoelAssistant::assertPrompted(static fn ($prompt) => $prompt->contains('Play some jazz'));
    }

    #[Test]
    public function requiresAuthentication(): void
    {
        KoelAssistant::fake();

        $this->postJson('api/ai/prompt', [
            'prompt' => 'Play some jazz',
        ])->assertUnauthorized();

        KoelAssistant::assertNeverPrompted();
    }

    #[Test]
    public function validatesPromptIsRequired(): void
    {
        KoelAssistant::fake();

        $user = create_user();

        $this->postAs('api/ai/prompt', [], $user)->assertUnprocessable();

        KoelAssistant::assertNeverPrompted();
    }

    #[Test]
    public function validatesPromptMaxLength(): void
    {
        KoelAssistant::fake();

        $user = create_user();

        $this->postAs(
            'api/ai/prompt',
            [
                'prompt' => str_repeat('a', 501),
            ],
            $user,
        )->assertUnprocessable();

        KoelAssistant::assertNeverPrompted();
    }
}
