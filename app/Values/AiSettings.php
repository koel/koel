<?php

namespace App\Values;

use App\Enums\AiSettingsSource;
use SensitiveParameter;

final readonly class AiSettings
{
    private function __construct(
        public AiSettingsSource $source,
        public bool $enabled,
        public ?string $provider,
        #[SensitiveParameter]
        public ?string $apiKey,
    ) {}

    public static function make(
        AiSettingsSource $source,
        bool $enabled = false,
        ?string $provider = null,
        #[SensitiveParameter]
        ?string $apiKey = null,
    ): self {
        return new self(source: $source, enabled: $enabled, provider: $provider, apiKey: $apiKey);
    }

    /**
     * A key saved for the organization is required; the server's configuration may name a provider that needs none.
     */
    public function isUsable(): bool
    {
        if (!$this->enabled || !$this->provider) {
            return false;
        }

        return $this->source === AiSettingsSource::Environment || (bool) $this->apiKey;
    }

    public function hasOrganizationKeyFor(string $provider): bool
    {
        return $this->source === AiSettingsSource::Organization && $this->apiKey && $this->provider === $provider;
    }
}
