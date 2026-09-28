<?php

namespace App\Services;

use Illuminate\Foundation\Vite;

class BuildIdentifier
{
    public function __construct(
        private readonly Vite $vite,
    ) {}

    public function getId(?string $buildIdPath = null): ?string
    {
        $buildIdPath ??= public_path('build/build-id');

        if ($this->vite->isRunningHot() || !is_file($buildIdPath)) {
            return null;
        }

        return trim((string) file_get_contents($buildIdPath)) ?: null;
    }
}
