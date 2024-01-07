<?php

namespace App\Services;

use App\Models\Song;
use App\Models\SongZipArchive;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class DownloadService
{
    public function __construct(private S3Service $s3Service)
    {
    }

    public function from(Collection $songs): string
    {
        if ($songs->count() === 1) {
            return $this->fromSong($songs->first());
        }

        return (new SongZipArchive())
            ->addSongs($songs)
            ->finish()
            ->getPath();
    }

    public function fromSong(Song $song): string
    {
        if ($song->s3_params) {
            // The song is hosted on Amazon S3.
            // We download it back to our local server first.
            $url = $this->s3Service->getSongPublicUrl($song);
            abort_unless((bool) $url, Response::HTTP_NOT_FOUND);

            $localPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . basename($song->s3_params['key']);

            // The following function requires allow_url_fopen to be ON.
            // We're just assuming that to be the case here.
            copy($url, $localPath);
        } else {
            // The song is hosted locally. Make sure the file exists.
            $localPath = $song->path;
            abort_unless(File::exists($localPath), Response::HTTP_NOT_FOUND);
        }

        return $localPath;
    }
}
