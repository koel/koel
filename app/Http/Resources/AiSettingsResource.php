<?php

namespace App\Http\Resources;

use App\Values\AiSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiSettingsResource extends JsonResource
{
    public function __construct(
        private readonly AiSettings $settings,
        private readonly AiSettings $serverSettings,
    ) {
        parent::__construct($settings);
    }

    /** @return array{source: string, enabled: bool, provider: ?string, has_api_key: bool, server_setup_usable: bool} */
    public function toArray(Request $request): array
    {
        return [
            'source' => $this->settings->source->value,
            'enabled' => $this->settings->enabled,
            'provider' => $this->settings->provider,
            'has_api_key' => (bool) $this->settings->apiKey,
            'server_setup_usable' => $this->serverSettings->isUsable(),
        ];
    }
}
