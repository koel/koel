<?php

namespace App\Values;

use Laravel\Ai\Enums\Lab;
use SensitiveParameter;

final readonly class AiSettings
{
    /** The labs that can run the assistant with nothing but an API key. */
    public const array PROVIDERS = [
        Lab::OpenAI,
        Lab::Anthropic,
        Lab::Gemini,
        Lab::DeepSeek,
        Lab::Groq,
        Lab::Mistral,
        Lab::OpenRouter,
        Lab::xAI,
    ];

    private function __construct(
        public bool $enabled,
        public ?Lab $provider,
        #[SensitiveParameter]
        public ?string $apiKey,
    ) {}

    public static function make(
        bool $enabled = false,
        ?Lab $provider = null,
        #[SensitiveParameter]
        ?string $apiKey = null,
    ): self {
        return new self(enabled: $enabled, provider: $provider, apiKey: $apiKey);
    }

    public function isUsable(): bool
    {
        return $this->enabled && $this->provider && $this->apiKey;
    }

    /** @return array{enabled: bool, provider: ?string, has_api_key: bool} */
    public function toArrayWithoutApiKey(): array
    {
        return [
            'enabled' => $this->enabled,
            'provider' => $this->provider?->value,
            'has_api_key' => (bool) $this->apiKey,
        ];
    }
}
