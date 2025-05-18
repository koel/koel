<?php

namespace App\Services;

use App\Enums\SongStorageType;
use App\Exceptions\LocalStorageRequiredException;
use App\Exceptions\MediaPathNotSetException;
use App\Facades\License;
use App\Models\Folder;
use App\Models\Setting;
use App\Models\Song;
use Illuminate\Support\Str;

class MediaBrowser
{
    /** @var array<string, Folder> */
    private static array $folderCache = [];

    public static function enabled(): bool
    {
        return config('koel.media_browser.enabled') && License::isPlus();
    }

    /**
     * @description Create a folder structure for the given song if it doesn't exist, and return the deepest folder.
     */
    public function maybeCreateFolderStructureForSong(Song $song, ?string $rootPath = null): ?Folder
    {
        throw_unless($song->storage === SongStorageType::LOCAL, LocalStorageRequiredException::class);

        $rootPath ??= Setting::get('media_path');

        // The song is already in a folder, so we don't need to create one.
        if ($song->folder_id) {
            return $song->folder;
        }

        throw_unless($rootPath, MediaPathNotSetException::class);

        $parentId = null;
        $currentPath = '';
        $folder = null;

        $relativePath = Str::after($song->path, $rootPath);
        $folderPath = pathinfo($relativePath, PATHINFO_DIRNAME);
        $segments = explode(DIRECTORY_SEPARATOR, trim($folderPath, DIRECTORY_SEPARATOR));

        if (!$segments) {
            return null;
        }

        foreach ($segments as $segment) {
            if (!$segment) {
                continue;
            }

            $currentPath = $currentPath ? sprintf('%s%s%s', $currentPath, DIRECTORY_SEPARATOR, $segment) : $segment;

            if (isset(self::$folderCache[$currentPath])) {
                $folder = self::$folderCache[$currentPath];
            } else {
                /** @var Folder $folder */
                $folder = Folder::query()->firstOrCreate(
                    ['path' => $currentPath],
                    ['parent_id' => $parentId]
                );

                self::$folderCache[$currentPath] = $folder;
            }

            $parentId = $folder->id;
        }

        return $folder;
    }
}
