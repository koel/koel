<?php

namespace App\Values;

use App\Enums\AiProvider;
use SensitiveParameter;

final readonly class AiSettings
{
    private function __construct(
        public bool $enabled,
        public ?AiProvider $provider,
        #[SensitiveParameter]
        public ?string $apiKey,
    ) {}

    public static function make(
        bool $enabled = false,
        ?AiProvider $provider = null,
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
