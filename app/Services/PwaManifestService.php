<?php

namespace App\Services;

use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PwaManifestService
{
    private const string PLACEHOLDER_HOST = 'https://your.koel.host';

    public function __construct(
        #[Config('app.url')]
        private readonly string $appUrl,
    ) {}

    /** @return array<mixed> */
    public function getAppManifest(): array
    {
        return $this->buildManifest((string) koel_branding('name'), $this->rootUrl());
    }

    /** @return array<mixed> */
    public function getRemoteManifest(): array
    {
        return $this->buildManifest(koel_branding('name') . ' Remote Controller', $this->rootUrl() . 'remote');
    }

    public function removeUncustomizedLegacyFiles(): void
    {
        foreach (['manifest.json', 'manifest-remote.json'] as $file) {
            $path = public_path($file);

            if (self::stillPointsAtPlaceholderHost($path)) {
                File::delete($path);
            }
        }
    }

    private function rootUrl(): string
    {
        return Str::finish($this->appUrl, '/');
    }

    /** @return array<mixed> */
    private function buildManifest(string $name, string $startUrl): array
    {
        return [
            'name' => $name,
            'start_url' => $startUrl,
            'display' => 'standalone',
            'orientation' => 'portrait',
            'icons' => [
                [
                    'src' => static_url('img/icon.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
            ],
            'background_color' => '#111111',
            'description' => 'Personal audio streaming service that works.',
            'theme_color' => '#111111',
        ];
    }

    private static function stillPointsAtPlaceholderHost(string $path): bool
    {
        if (!File::isFile($path)) {
            return false;
        }

        $startUrl = rescue(static fn () => json_decode(File::get($path), true)['start_url'] ?? null, report: false);

        return is_string($startUrl) && str_starts_with($startUrl, self::PLACEHOLDER_HOST);
    }
}
