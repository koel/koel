<?php

namespace App\Services;

use Illuminate\Foundation\Vite;

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
