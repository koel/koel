<?php

namespace App\Values;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

/** @implements Arrayable<string, mixed> */
final readonly class PresignedUpload implements Arrayable
{
    /** @param array<string, string> $headers */
    private function __construct(
        public string $key,
        public string $url,
        public array $headers,
        public Carbon $expiresAt,
    ) {}

    /** @param array<string, string> $headers */
    public static function make(string $key, string $url, array $headers, Carbon $expiresAt): self
    {
        return new self($key, $url, $headers, $expiresAt);
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'url' => $this->url,
            'headers' => $this->headers,
            'expires_at' => $this->expiresAt->toIso8601String(),
        ];
    }
}
