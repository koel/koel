<?php

namespace App\Services\SongStorage;

use App\Models\Song;
use App\Models\User;
use App\Services\FileScanner;
use App\Values\SongStorageTypes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use League\Flysystem\Filesystem;
use Spatie\Dropbox\Client;
use Spatie\FlysystemDropbox\DropboxAdapter;

final class DropboxStorage extends CloudStorage
{
    public Filesystem $filesystem;
    private DropboxAdapter $adapter;

    public function __construct(protected FileScanner $scanner, private array $config)
    {
        parent::__construct($scanner);

        $client = new Client($this->maybeRefreshAccessToken());
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
            $song->update([
                'path' => "dropbox://$key",
                'storage' => SongStorageTypes::DROPBOX,
            ]);

            File::delete($result->path);

            return $song;
        });
    }

    private function maybeRefreshAccessToken(): string
    {
        $accessToken = Cache::get('dropbox_access_token');

        if ($accessToken) {
            return $accessToken;
        }

        $response = Http::asForm()
            ->withBasicAuth($this->config['app_key'], $this->config['app_secret'])
            ->post('https://api.dropboxapi.com/oauth2/token', [
                'refresh_token' => $this->config['refresh_token'],
                'grant_type' => 'refresh_token',
            ])->json();

        Cache::put(
            'dropbox_access_token',
            $response['access_token'],
            now()->addSeconds($response['expires_in'] - 60) // 60 seconds buffer
        );

        return  $response['access_token'];
    }

    public function getSongPresignedUrl(Song $song): string
    {
        return $this->adapter->getUrl($song->storage_metadata->getPath());
    }

    public function supported(): bool
    {
        return SongStorageTypes::supported(SongStorageTypes::DROPBOX);
    }

    public function testSetup(): void
    {
        $this->filesystem->write('test.txt', 'Koel test file.');
        $this->filesystem->delete('test.txt');
    }
}
