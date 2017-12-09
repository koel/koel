<?php

namespace App\Services;

use App\Console\Commands\SyncMedia;
use App\Events\LibraryChanged;
use App\Libraries\WatchRecord\WatchRecordInterface;
use App\Models\Album;
use App\Models\Artist;
use App\Models\File;
use App\Models\Setting;
use App\Models\Song;
use Exception;
use getID3;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Finder\Finder;

class Media
{
    /**
     * All applicable tags in a media file that we cater for.
     * Note that each isn't necessarily a valid ID3 tag name.
     *
     * @var array
     */
    protected $allTags = [
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

    /**
     * Tags to be synced.
     *
     * @var array
     */
    protected $tags = [];

    /**
     * Sync the media. Oh sync the media.
     *
     * @param string|null $mediaPath
     * @param array       $tags        The tags to sync.
     *                                 Only taken into account for existing records.
     *                                 New records will have all tags synced in regardless.
     * @param bool        $force       Whether to force syncing even unchanged files
     * @param SyncMedia   $syncCommand The SyncMedia command object, to log to console if executed by artisan.
     *
     * @throws Exception
     */
    public function sync($mediaPath = null, $tags = [], $force = false, SyncMedia $syncCommand = null)
    {
        if (!app()->runningInConsole()) {
            set_time_limit(config('koel.sync.timeout'));
        }

        if (config('koel.memory_limit')) {
            ini_set('memory_limit', config('koel.memory_limit').'M');
        }

        $mediaPath = $mediaPath ?: Setting::get('media_path');
        $this->setTags($tags);

        $results = [
            'success' => [],
            'bad_files' => [],
            'unmodified' => [],
        ];

        $getID3 = new getID3();
        $songPaths = $this->gatherFiles($mediaPath);
        $syncCommand && $syncCommand->createProgressBar(count($songPaths));

        foreach ($songPaths as $path) {
            $file = new File($path, $getID3);

            switch ($result = $file->sync($this->tags, $force)) {
                case File::SYNC_RESULT_SUCCESS:
                    $results['success'][] = $file;
                    break;
                case File::SYNC_RESULT_UNMODIFIED:
                    $results['unmodified'][] = $file;
                    break;
                default:
                    $results['bad_files'][] = $file;
                    break;
            }

            if ($syncCommand) {
                $syncCommand->updateProgressBar();
                $syncCommand->logToConsole($file->getPath(), $result, $file->getSyncError());
            }
        }

        // Delete non-existing songs.
        $hashes = array_map(function (File $file) {
            return self::getHash($file->getPath());
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
     * @return array An array of SplFileInfo objects
     */
    public function gatherFiles($path)
    {
        return iterator_to_array(
            Finder::create()
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
     * @param WatchRecordInterface $record The watch record.
     *
     * @throws Exception
     */
    public function syncByWatchRecord(WatchRecordInterface $record)
    {
        Log::info("New watch record received: '$record'");
        $record->isFile() ? $this->syncFileRecord($record) : $this->syncDirectoryRecord($record);
    }

    /**
     * Sync a file's watch record.
     *
     * @param WatchRecordInterface $record
     *
     * @throws Exception
     */
    private function syncFileRecord(WatchRecordInterface $record)
    {
        $path = $record->getPath();
        Log::info("'$path' is a file.");

        // If the file has been deleted...
        if ($record->isDeleted()) {
            // ...and it has a record in our database, remove it.
            if ($song = Song::byPath($path)) {
                $song->delete();
                Log::info("$path deleted.");

                event(new LibraryChanged());
            } else {
                Log::info("$path doesn't exist in our database--skipping.");
            }
        }
        // Otherwise, it's a new or changed file. Try to sync it in.
        // File format etc. will be handled by File::sync().
        elseif ($record->isNewOrModified()) {
            $result = (new File($path))->sync($this->tags);
            Log::info($result === File::SYNC_RESULT_SUCCESS ? "Synchronized $path" : "Invalid file $path");

            event(new LibraryChanged());
        }
    }

    /**
     * Sync a directory's watch record.
     *
     * @param WatchRecordInterface $record
     */
    private function syncDirectoryRecord(WatchRecordInterface $record)
    {
        $path = $record->getPath();
        Log::info("'$path' is a directory.");

        if ($record->isDeleted()) {
            // The directory is removed. We remove all songs in it.
            if ($count = Song::inDirectory($path)->delete()) {
                Log::info("Deleted $count song(s) under $path");

                event(new LibraryChanged());
            } else {
                Log::info("$path is empty--no action needed.");
            }
        } elseif ($record->isNewOrModified()) {
            foreach ($this->gatherFiles($path) as $file) {
                (new File($file))->sync($this->tags);
            }
            Log::info("Synced all song(s) under $path");

            event(new LibraryChanged());
        }
    }

    /**
     * Construct an array of tags to be synced into the database from an input array of tags.
     * If the input array is empty or contains only invalid items, we use all tags.
     * Otherwise, we only use the valid items in it.
     *
     * @param array $tags
     */
    public function setTags($tags = [])
    {
        $this->tags = array_intersect((array) $tags, $this->allTags) ?: $this->allTags;

        // We always keep track of mtime.
        if (!in_array('mtime', $this->tags, true)) {
            $this->tags[] = 'mtime';
        }
    }

    /**
     * Generate a unique hash for a file path.
     *
     * @param $path
     *
     * @return string
     */
    public function getHash($path)
    {
        return File::getHash($path);
    }

    /**
     * Tidy up the library by deleting empty albums and artists.
     *
     * @throws Exception
     */
    public function tidy()
    {
        $inUseAlbums = Song::select('album_id')
            ->groupBy('album_id')
            ->get()
            ->pluck('album_id')
            ->toArray();
        $inUseAlbums[] = Album::UNKNOWN_ID;
        Album::deleteWhereIDsNotIn($inUseAlbums);

        $inUseArtists = Song::select('artist_id')
            ->groupBy('artist_id')
            ->get()
            ->pluck('artist_id')
            ->toArray();
        $inUseArtists[] = Artist::UNKNOWN_ID;
        $inUseArtists[] = Artist::VARIOUS_ID;
        Artist::deleteWhereIDsNotIn(array_filter($inUseArtists));
    }
}
