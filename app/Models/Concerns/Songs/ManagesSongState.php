<?php

namespace App\Models\Concerns\Songs;

use App\Enums\PlayableType;
use App\Enums\SongStorageType;
use App\Models\Genre;
use LogicException;

trait ManagesSongState
{
    public function syncGenres(string|array $genres): void
    {
        $genreNames = is_array($genres) ? $genres : explode(',', $genres);

        $genreIds = collect($genreNames)
            // @mago-ignore lint:prefer-first-class-callable
            ->map(static fn(string $name) => trim($name))
            ->filter()
            ->unique()
            ->map(static fn(string $name) => Genre::get($name)->id);

        $this->genres()->sync($genreIds);
    }

    public function isEpisode(): bool
    {
        return $this->type === PlayableType::PODCAST_EPISODE;
    }

    public function genreEqualsTo(string|array $genres): bool
    {
        $genreNames = collect(is_string($genres) ? explode(',', $genres) : $genres)
            // @mago-ignore lint:prefer-first-class-callable
            ->map(static fn(string $name) => trim($name))
            ->filter()
            ->unique()
            ->sort()
            ->join(', ');

        if (!$this->genre && !$genreNames) {
            return true;
        }

        return $this->genre === $genreNames;
    }

    public function isStoredOnCloud(): bool
    {
        return in_array(
            $this->storage,
            [
                SongStorageType::S3,
                SongStorageType::S3_LAMBDA,
                SongStorageType::DROPBOX
            ],
            true
        );
    }

    /**
     * Determine if the song's associated file has been modified since the last scan.
     * This is done by comparing the stored hash or mtime with the corresponding
     * value from the scan information.
     */
    public function isFileModified(int $lastModified): bool
    {
        throw_if($this->isEpisode(), new LogicException('Podcast episodes do not have associated files.'));

        return $this->mtime !== $lastModified;
    }
}
