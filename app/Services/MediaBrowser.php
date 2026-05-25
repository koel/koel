<?php

namespace App\Services;

use App\Exceptions\MediaBrowserNotSupportedException;
use App\Exceptions\MediaPathNotSetException;
use App\Facades\License;
use App\Models\Folder;
use App\Models\Setting;
use App\Models\Song;
use App\Repositories\FolderRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MediaBrowser
{
    /** @var array<string, Folder> */
    private static array $folderCache = [];

    private static ?string $mediaPath;

    public function __construct(
        private readonly FolderRepository $folderRepository,
    ) {}

    public static function used(): bool
    {
        return config('koel.media_browser.enabled') && License::isPlus();
    }

    /**
     * @return array{
     *     current: ?Folder,
     *     ancestors: Collection<int, Folder>,
     *     subfolders: Collection<int, Folder>,
     * }
     */
    public function getSubfolderView(?Folder $folder): array
    {
        $ancestors = $folder ? $this->folderRepository->getAncestors($folder) : new Collection();
        $subfolders = $this->folderRepository->getSubfolders($folder);

        $batch = $ancestors->merge($subfolders);

        if ($folder) {
            $batch->push($folder);
        }

        $batch->loadMissing('uploader');

        return [
            'current' => $folder,
            'ancestors' => $ancestors,
            'subfolders' => $subfolders,
        ];
    }

    /**
     * Create a folder structure for the given song if it doesn't exist, and return the deepest folder.
     * For example, if the song's path is `/root/media/path/foo/bar/baz.mp3`, it will create the folders with paths
     * "foo" and "foo/bar" if they don't exist, and return the folder with path "foo/bar".
     * For efficiency, we also cache the folders and the media path to avoid multiple database queries.
     * This is particularly useful when processing multiple songs in a batch (e.g., during scanning).
     */
    public function maybeCreateFolderStructureForSong(Song $song): void
    {
        throw_unless($song->storage->supportsFolderStructureExtraction(), MediaBrowserNotSupportedException::class);

        // The song is already in a folder, so we don't need to create one.
        if ($song->folder_id) {
            return;
        }

        self::$mediaPath ??= Setting::get('media_path');

        throw_unless(self::$mediaPath, MediaPathNotSetException::class);

        $prefix = rtrim(self::$mediaPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!str_starts_with($song->path, $prefix)) {
            Log::warning(sprintf(
                'Skipping folder structure for song %s: path %s is outside media_path %s',
                $song->id,
                $song->path,
                self::$mediaPath,
            ));

            return;
        }

        $parentId = null;
        $currentPath = '';
        $folder = null;

        $relativePath = Str::after($song->path, self::$mediaPath);
        $folderPath = pathinfo($relativePath, PATHINFO_DIRNAME);
        $segments = explode(DIRECTORY_SEPARATOR, trim($folderPath, DIRECTORY_SEPARATOR));

        // For each segment in the folder path ('foo' and 'bar'), we will create a folder if it doesn't exist
        // using the aggregated path (e.g., 'foo' and 'foo/bar').
        foreach ($segments as $segment) {
            if (!$segment) {
                continue;
            }

            $currentPath = $currentPath ? sprintf('%s%s%s', $currentPath, DIRECTORY_SEPARATOR, $segment) : $segment;

            if (isset(self::$folderCache[$currentPath])) {
                $folder = self::$folderCache[$currentPath];
            } else {
                /** @var Folder $folder */
                $folder = Folder::query()->firstOrCreate(['hash' => simple_hash($currentPath)], [
                    'parent_id' => $parentId,
                    'path' => $currentPath,
                ]);

                self::$folderCache[$currentPath] = $folder;
            }

            $parentId = $folder->id;
        }

        if ($folder) {
            $song->folder()->associate($folder);
            $song->save();
        }
    }

    public static function clearCache(): void
    {
        self::$folderCache = [];
        self::$mediaPath = null;
    }
}
