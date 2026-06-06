<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmbedOptionsTest extends TestCase
{
    #[Test]
    public function encrypt(): void
    {
        $this
            ->post('api/embed-options', [
                'theme' => 'cat',
                'layout' => 'compact',
                'preview' => true,
            ])
            ->assertSuccessful()
            ->assertJsonStructure(['encrypted']);
    }

    #[Test]
    public function encryptReturnsNotFoundWhenEmbeddingDisabled(): void
    {
        config(['koel.embed.enabled' => false]);

        $this->post('api/embed-options', [
            'theme' => 'cat',
            'layout' => 'compact',
            'preview' => true,
        ])->assertNotFound();
    }
}
