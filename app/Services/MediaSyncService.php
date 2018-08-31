<?php

namespace App\Services;

use App\Console\Commands\SyncMediaCommand;
use App\Events\LibraryChanged;
use App\Libraries\WatchRecord\WatchRecordInterface;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Setting;
use App\Models\Song;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SettingRepository;
use App\Repositories\SongRepository;
use Exception;
use Illuminate\Log\Logger;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class MediaSyncService
{
    /**
     * All applicable tags in a media file that we cater for.
     * Note that each isn't necessarily a valid ID3 tag name.
     *
     * @var array
     */
    private const APPLICABLE_TAGS = [
        'artist',
        'album',
        'title',
        'length',
        'track',
        'disc',
        'lyrics',
        'cover',
        'mtime',
        'compilation',
    ];

    private $mediaMetadataService;
    private $songRepository;
    private $helperService;
    private $fileSynchronizer;
    private $finder;
    private $artistRepository;
    private $albumRepository;
    private $settingRepository;
    private $logger;

    public function __construct(
        MediaMetadataService $mediaMetadataService,
        SongRepository $songRepository,
        ArtistRepository $artistRepository,
        AlbumRepository $albumRepository,
        SettingRepository $settingRepository,
        HelperService $helperService,
        FileSynchronizer $fileSynchronizer,
        Finder $finder,
        Logger $logger
    ) {
        $this->mediaMetadataService = $mediaMetadataService;
        $this->songRepository = $songRepository;
        $this->helperService = $helperService;
        $this->fileSynchronizer = $fileSynchronizer;
        $this->finder = $finder;
        $this->artistRepository = $artistRepository;
        $this->albumRepository = $albumRepository;
        $this->settingRepository = $settingRepository;
        $this->logger = $logger;
    }

    /**
     * Tags to be synced.
     *
     * @var array
     */
    protected $tags = [];

    /**
     * Sync the media. Oh sync the media.
     *
     * @param string[]         $tags        The tags to sync.
     *                                      Only taken into account for existing records.
     *                                      New records will have all tags synced in regardless.
     * @param bool             $force       Whether to force syncing even unchanged files
     * @param SyncMediaCommand $syncCommand The SyncMedia command object, to log to console if executed by artisan.
     *
     * @throws Exception
     */
    public function sync(
        ?string $mediaPath = null,
        array $tags = [],
        bool $force = false,
        ?SyncMediaCommand $syncCommand = null
    ): void {
        $this->setSystemRequirements();
        $this->setTags($tags);

        $results = [
            'success' => [],
            'bad_files' => [],
            'unmodified' => [],
        ];

        $songPaths = $this->gatherFiles($mediaPath ?: Setting::get('media_path'));
        $syncCommand && $syncCommand->createProgressBar(count($songPaths));

        foreach ($songPaths as $path) {
            $result = $this->fileSynchronizer->setFile($path)->sync($this->tags, $force);

            switch ($result) {
                case FileSynchronizer::SYNC_RESULT_SUCCESS:
                    $results['success'][] = $path;
                    break;
                case FileSynchronizer::SYNC_RESULT_UNMODIFIED:
                    $results['unmodified'][] = $path;
                    break;
                default:
                    $results['bad_files'][] = $path;
                    break;
            }

            if ($syncCommand) {
                $syncCommand->advanceProgressBar();
                $syncCommand->logSyncStatusToConsole($path, $result, $this->fileSynchronizer->getSyncError());
            }
        }

        // Delete non-existing songs.
        $hashes = array_map(function (string $path): string {
            return $this->helperService->getFileHash($path);
        }, array_merge($results['unmodified'], $results['success']));

        Song::deleteWhereIDsNotIn($hashes);

        // Trigger LibraryChanged, so that TidyLibrary handler is fired to, erm, tidy our library.
        event(new LibraryChanged());
    }

    /**
     * Gather all applicable files in a given directory.
     *
     * @param string $path The directory's full path
     *
     * @return SplFileInfo[]
     */
    public function gatherFiles(string $path): array
    {
        return iterator_to_array(
            $this->finder->create()
                ->ignoreUnreadableDirs()
                ->ignoreDotFiles((bool) config('koel.ignore_dot_files')) // https://github.com/phanan/koel/issues/450
                ->files()
                ->followLinks()
                ->name('/\.(mp3|ogg|m4a|flac)$/i')
                ->in($path)
        );
    }

    /**
     * Sync media using a watch record.
     *
     * @throws Exception
     */
    public function syncByWatchRecord(WatchRecordInterface $record): void
    {
        $this->logger->info("New watch record received: '$record'");
        $record->isFile() ? $this->syncFileRecord($record) : $this->syncDirectoryRecord($record);
    }

    /**
     * Sync a file's watch record.
     *
     * @throws Exception
     */
    private function syncFileRecord(WatchRecordInterface $record): void
    {
        $path = $record->getPath();
        $this->logger->info("'$path' is a file.");

        // If the file has been deleted...
        if ($record->isDeleted()) {
            $this->handleDeletedFileRecord($path);
        }
        // Otherwise, it's a new or changed file. Try to sync it in.
        elseif ($record->isNewOrModified()) {
            $this->handleNewOrModifiedFileRecord($path);
        }
    }

    /**
     * Sync a directory's watch record.
     */
    private function syncDirectoryRecord(WatchRecordInterface $record): void
    {
        $path = $record->getPath();
        $this->logger->info("'$path' is a directory.");

        if ($record->isDeleted()) {
            $this->handleDeletedDirectoryRecord($path);
        } elseif ($record->isNewOrModified()) {
            $this->handleNewOrModifiedDirectoryRecord($path);
        }
    }

    /**
     * Construct an array of tags to be synced into the database from an input array of tags.
     * If the input array is empty or contains only invalid items, we use all tags.
     * Otherwise, we only use the valid items in it.
     *
     * @param string[] $tags
     */
    public function setTags(array $tags = []): void
    {
        $this->tags = array_intersect((array) $tags, self::APPLICABLE_TAGS) ?: self::APPLICABLE_TAGS;

        // We always keep track of mtime.
        if (!in_array('mtime', $this->tags, true)) {
            $this->tags[] = 'mtime';
        }
    }

    /**
     * Tidy up the library by deleting empty albums and artists.
     *
     * @throws Exception
     */
    public function tidy(): void
    {
        $inUseAlbums = $this->albumRepository->getNonEmptyAlbumIds();
        $inUseAlbums[] = Album::UNKNOWN_ID;
        Album::deleteWhereIDsNotIn($inUseAlbums);

        $inUseArtists = $this->artistRepository->getNonEmptyArtistIds();
        $inUseArtists[] = Artist::UNKNOWN_ID;
        $inUseArtists[] = Artist::VARIOUS_ID;
        Artist::deleteWhereIDsNotIn(array_filter($inUseArtists));
    }

    private function setSystemRequirements(): void
    {
        if (!app()->runningInConsole()) {
            set_time_limit(config('koel.sync.timeout'));
        }

        if (config('koel.memory_limit')) {
            ini_set('memory_limit', config('koel.memory_limit').'M');
        }
    }

    /**
     * @throws Exception
     */
    private function handleDeletedFileRecord(string $path): void
    {
        if ($song = $this->songRepository->getOneByPath($path)) {
            $song->delete();
            $this->logger->info("$path deleted.");

            event(new LibraryChanged());
        } else {
            $this->logger->info("$path doesn't exist in our database--skipping.");
        }
    }

    private function handleNewOrModifiedFileRecord(string $path): void
    {
        $result = $this->fileSynchronizer->setFile($path)->sync($this->tags);

        if ($result === FileSynchronizer::SYNC_RESULT_SUCCESS) {
            $this->logger->info("Synchronized $path");
        } else {
            $this->logger->info("Failed to synchronized $path. Maybe an invalid file?");
        }

        event(new LibraryChanged());
    }

    private function handleDeletedDirectoryRecord(string $path): void
    {
        if ($count = Song::inDirectory($path)->delete()) {
            $this->logger->info("Deleted $count song(s) under $path");

            event(new LibraryChanged());
        } else {
            $this->logger->info("$path is empty--no action needed.");
        }
    }

    private function handleNewOrModifiedDirectoryRecord(string $path): void
    {
        foreach ($this->gatherFiles($path) as $file) {
            $this->fileSynchronizer->setFile($file)->sync($this->tags);
        }

        $this->logger->info("Synced all song(s) under $path");

        event(new LibraryChanged());
    }
}
