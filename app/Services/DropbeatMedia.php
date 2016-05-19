<?php

namespace App\Services;

use App\Events\LibraryChanged;
use App\Libraries\WatchRecord\WatchRecordInterface;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Setting;
use App\Models\Song;
use Exception;
use getID3;
use getid3_lib;
use Illuminate\Support\Facades\Log;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class DropbeatMedia
{
    /**
     * Sync the media. Oh sync the media.
     *
     * @param string|null $path
     * @param SyncMedia   $syncCommand The SyncMedia command object, to log to console if executed by artisan.
     */
    public function sync($request)
    {
        Log::info('app.requests', ['request' => $request->all()]);

        $Dsong = $request;

        // $results = [
        //     'good' => [], // Updated or added files
        //     'bad' => [], // Bad files
        //     'ugly' => [], // Unmodified files
        // ];


        $song = $this->syncFile($Dsong);

        // if ($song === true) {
        //     $results['ugly'][] = $file;
        // } elseif ($song === false) {
        //     $results['bad'][] = $file;
        // } else {
        //     $results['good'][] = $file;
        // }

        // Delete non-existing songs.
        // $hashes = array_map(function ($f) {
        //     return $this->getHash($f->getPathname());
        // }, array_merge($results['ugly'], $results['good']));
        //
        // Song::whereNotIn('id', $hashes)->delete();

        // Trigger LibraryChanged, so that TidyLibrary handler is fired to, erm, tidy our library.
        event(new LibraryChanged());
    }

    /**
     * Sync a song with all available media info against the database.
     *
     * @param SplFileInfo|string $file The SplFileInfo instance of the file, or the file path.
     *
     * @return bool|Song A Song object on success,
     *                   true if file exists but is unmodified,
     *                   or false on an error.
     */
    public function syncFile($Dsong)
    {
        // if (!($file instanceof SplFileInfo)) {
        //     $file = new SplFileInfo($file);
        // }

        if (!$info = $this->getInfo($Dsong)) {
            return false;
        }

        Log::info('app.requests', ['info' => $info]);

        // if (!$this->isNewOrChanged($file)) {
        //     return true;
        // }

        $artist = Artist::get($info['artist']);
        $album = Album::get($artist, $info['album']);

        if ($info['cover'] && !$album->has_cover) {
            try {
                $album->generateCover($info['cover']);
            } catch (Exception $e) {
                Log::error($e);
            }
        }

        $info['album_id'] = $album->id;

        unset($info['artist']);//변수파괴
        unset($info['album']);
        unset($info['cover']);

        $song = Song::updateOrCreate(['id' => $this->getHash($Dsong->input('id'))], $info);
        Log::info('app.requests', ['song' => $song]);
        $song->save();

        return $song;
    }


    /**
     * Check if a media file is new or changed.
     * A file is considered existing and unchanged only when:
     * - its hash (ID) can be found in the database, and
     * - its last modified time is the same with that of the comparing file.
     *
     * @param SplFileInfo $file
     *
     * @return bool
     */
    // protected function isNewOrChanged(SplFileInfo $file)
    // {
    //     return !Song::whereIdAndMtime($this->getHash($file->getPathname()), $file->getMTime())->count();
    // }

    /**
     * Get ID3 info from a file.
     *
     * @param SplFileInfo $file
     *
     * @return array|null
     */
    public function getInfo($Dsong)
    {
        // if (!isset($info['playtime_seconds'])) {
        //     return;
        // }

        $props = [
            'artist' => '',
            'album' => '',
            'album_id' => '',
            'title' => $Dsong->input('title'),
            'length' => 278.86,
            'lyrics' => '',
            'cover' => '',
            'path' => $Dsong->input('id'),
            'type' => $Dsong->input('type'),
            'mtime' => date("Ymd"),
        ];

        return $props;
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
        return md5(config('app.key').$path);
    }
}
