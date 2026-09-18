<?php

namespace App\Services\Image;

use Illuminate\Support\Facades\File;

class LegacyArtworkMigrator
{
    public const string LEGACY_DIR = 'img/storage';

    public function pendingCount(): int
    {
        return $this->hasLegacyDirectory() ? count(File::allFiles(self::legacyDirectory())) : 0;
    }

    /**
     * Copy every legacy image onto the configured disk, removing the originals as they land.
     *
     * @return bool Whether every image made it across.
     */
    public function migrate(): bool
    {
        if (!$this->hasLegacyDirectory()) {
            return true;
        }

        $disk = ImageStorage::disk();
        $migrated = true;

        foreach (File::allFiles(self::legacyDirectory()) as $file) {
            $key = $file->getRelativePathname();
            $stored = $disk->exists($key) || $disk->put($key, File::get($file->getPathname())) !== false;

            $migrated = $stored && File::delete($file->getPathname()) && $migrated;
        }

        if ($migrated) {
            File::deleteDirectory(self::legacyDirectory());
        }

        return $migrated;
    }

    public function hasLegacyDirectory(): bool
    {
        return File::isDirectory(self::legacyDirectory());
    }

    private static function legacyDirectory(): string
    {
        return public_path(self::LEGACY_DIR);
    }
}
