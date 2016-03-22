<?php

namespace App\Services;

use App\Console\Commands\SyncMedia;
use App\Events\LibraryChanged;
use App\Libraries\WatchRecord\WatchRecordInterface;
use App\Models\File;
use App\Models\Setting;
use App\Models\Song;
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
    protected $allTags = ['artist', 'album', 'title', 'length', 'track', 'lyrics', 'cover', 'mtime'];

    /**
     * Tags to be synced.
     *
     * @var array
     */
    protected $tags = [];

    public function __construct()
    {
    }

    /**
     * Sync the media. Oh sync the media.
     *
     * @param string|null $path
     * @param array       $tags        The tags to sync.
     *                                 Only taken into account for existing records.
     *                                 New records will have all tags synced in regardless.
     * @param bool        $force       Whether to force syncing even unchanged files
     * @param SyncMedia   $syncCommand The SyncMedia command object, to log to console if executed by artisan.
     */
    public function sync($path = null, $tags = [], $force = false, SyncMedia $syncCommand = null)
    {
        if (!app()->runningInConsole()) {
            set_time_limit(env('APP_MAX_SCAN_TIME', 600));
        }

        $path = $path ?: Setting::get('media_path');
        $this->setTags($tags);

        $results = [
            'good' => [], // Updated or added files
            'bad' => [], // Bad files
            'ugly' => [], // Unmodified files
        ];

        $getID3 = new getID3();

        foreach ($this->gatherFiles($path) as $file) {
            $file = new File($file, $getID3);

            $song = $file->sync($this->tags, $force);

            if ($song === true) {
                $results['ugly'][] = $file;
            } elseif ($song === false) {
                $results['bad'][] = $file;
            } else {
                $results['good'][] = $file;
            }

            if ($syncCommand) {
                $syncCommand->logToConsole($file->getPath(), $song);
            }
        }

        // Delete non-existing songs.
        $hashes = array_map(function ($f) {
            return self::getHash($f->getPath());
        }, array_merge($results['ugly'], $results['good']));

        Song::whereNotIn('id', $hashes)->delete();

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
        return Finder::create()->files()->name('/\.(mp3|ogg|m4a|flac)$/i')->in($path);
    }

    /**
     * Sync media using a watch record.
     *
     * @param WatchRecordInterface $record      The watch record.
     * @param SyncMedia|null       $syncCommand The SyncMedia command object, to log to console if executed by artisan.
     */
    public function syncByWatchRecord(WatchRecordInterface $record, SyncMedia $syncCommand = null)
    {
        Log::info("New watch record received: '$record'");
        $path = $record->getPath();

        if ($record->isFile()) {
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
            // File format etc. will be handled by the syncFile method.
            elseif ($record->isNewOrModified()) {
                $result = (new File($path))->sync($this->tags);
                Log::info($result instanceof Song ? "Synchronized $path" : "Invalid file $path");
            }

            return;
        }

        // Record is a directory.
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
        }
    }

    /**
     * Construct an array of tags to be synced into the database from an input array of tags.
     * If the input array is empty or contains only invalid items, we use all tags.
     * Otherwise, we only use the valid items it it.
     *
     * @param array $tags
     *
     * @return array
     */
    public function setTags($tags = [])
    {
        $this->tags = array_intersect((array) $tags, $this->allTags) ?: $this->allTags;

        // We always keep track of mtime.
        if (!in_array('mtime', $this->tags)) {
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
}
