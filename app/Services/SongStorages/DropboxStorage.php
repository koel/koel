<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Filesystems\DropboxFilesystem;
use App\Models\Song;
use App\Models\User;
use App\Services\FileScanner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

final class DropboxStorage extends CloudStorage
{
    public function __construct(
        protected FileScanner $scanner,
        private readonly DropboxFilesystem $filesystem,
        private readonly array $config
    ) {
        parent::__construct($scanner);

        $this->filesystem->getAdapter()->getClient()->setAccessToken($this->maybeRefreshAccessToken());
    }

    public function storeUploadedFile(UploadedFile $file, User $uploader): Song
    {
        self::assertSupported();

        return DB::transaction(function () use ($file, $uploader): Song {
            $result = $this->scanUploadedFile($file, $uploader);
            $song = $this->scanner->getSong();
            $key = $this->generateStorageKey($file->getClientOriginalName(), $uploader);

            $this->filesystem->write($key, File::get($result->path));

            $song->update([
                'path' => "dropbox://$key",
                'storage' => SongStorageType::DROPBOX,
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
            ]);

        Cache::put(
            'dropbox_access_token',
            $response->json('access_token'),
            now()->addSeconds($response->json('expires_in') - 60) // 60 seconds buffer
        );

        return $response->json('access_token');
    }

    public function getSongPresignedUrl(Song $song): string
    {
        self::assertSupported();

        return $this->filesystem->temporaryUrl($song->storage_metadata->getPath());
    }

    public function supported(): bool
    {
        return SongStorageType::DROPBOX->supported();
    }

    public function delete(Song $song, bool $backup = false): void
    {
        self::assertSupported();

        $path = $song->storage_metadata->getPath();

        if ($backup) {
            $this->filesystem->move($path, "backup/$path");
        }

        $this->filesystem->delete($path);
    }

    public function testSetup(): void
    {
        $this->filesystem->write('test.txt', 'Koel test file.');
        $this->filesystem->delete('test.txt');
    }
}
