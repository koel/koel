<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Exceptions\SongUploadFailedException;
use App\Filesystems\DropboxFilesystem;
use App\Models\Song;
use App\Models\User;
use App\Services\Scanner\FileScanner;
use App\Services\SongStorages\Concerns\DeletesUsingFilesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

class DropboxStorage extends CloudStorage
{
    use DeletesUsingFilesystem;

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
        $result = $this->scanUploadedFile($this->scanner, $file, $uploader);
        $song = $this->scanner->getSong();
        $key = $this->generateStorageKey($file->getClientOriginalName(), $uploader);

        try {
            $this->uploadToStorage($key, $result->path);

            $song->update([
                'path' => "dropbox://$key",
                'storage' => SongStorageType::DROPBOX,
            ]);

            return $song;
        } catch (Throwable $e) {
            throw SongUploadFailedException::fromThrowable($e);
        } finally {
            File::delete($result->path);
        }
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

    public function getPresignedUrl(string $key): string
    {
        return $this->filesystem->temporaryUrl($key);
    }

    public function delete(string $location, bool $backup = false): void
    {
        $this->deleteFileWithKey($location, $backup);
    }

    public function testSetup(): void
    {
        $this->filesystem->write('test.txt', 'Koel test file.');
        $this->filesystem->delete('test.txt');
    }

    public function getStorageType(): SongStorageType
    {
        return SongStorageType::DROPBOX;
    }

    public function uploadToStorage(string $key, string $path): void
    {
        $this->filesystem->write($key, File::get($path));
    }

    public function deleteFileWithKey(string $key, bool $backup): void
    {
        $this->deleteUsingFilesystem($this->filesystem, $key, $backup);
    }
}
