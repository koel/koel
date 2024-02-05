<?php

namespace App\Services\SongStorage;

use App\Models\Song;
use App\Models\User;
use App\Services\FileScanner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use League\Flysystem\Filesystem;
use Spatie\Dropbox\Client;
use Spatie\FlysystemDropbox\DropboxAdapter;

class DropboxStorage extends CloudStorage
{
    private Filesystem $filesystem;
    private DropboxAdapter $adapter;

    public function __construct(protected FileScanner $scanner, private string $token, private string $folder)
    {
        parent::__construct($scanner);

        $client = new Client($this->token);
        $this->adapter = new DropboxAdapter($client);
        $this->filesystem = new Filesystem($this->adapter, ['case_sensitive' => false]);
    }

    public function storeUploadedFile(UploadedFile $file, User $uploader): Song
    {
        return DB::transaction(function () use ($file, $uploader): Song {
            $result = $this->scanUploadedFile($file, $uploader);
            $song = $this->scanner->getSong();
            $key = $this->generateStorageKey($file->getClientOriginalName(), $uploader);

            $this->filesystem->write($key, File::get($result->path));
            $song->update(['path' => "dropbox://$this->folder/$key"]);

            File::delete($result->path);

            return $song;
        });
    }

    public function getSongPresignedUrl(Song $song): string
    {
        return $this->adapter->getUrl($song->storage_metadata->getPath());
    }
}
