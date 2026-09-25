<?php

namespace App\Services;

use Illuminate\Foundation\Vite;

/**
 * Identifies the live frontend build by when its Vite manifest was written: every build writes a new
 * manifest, and checking the time costs a file stat, where hashing the manifest would read all of it.
 */
class BuildIdentifier
{
    public function __construct(
        private readonly Vite $vite,
    ) {}

    public function getId(?string $manifestPath = null): ?string
    {
        $manifestPath ??= public_path('build/manifest.json');

        if ($this->vite->isRunningHot() || !is_file($manifestPath)) {
            return null;
        }

        return (string) filemtime($manifestPath);
    }
}
