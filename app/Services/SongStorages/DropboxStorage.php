<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Filesystems\DropboxFilesystem;
use App\Models\User;
use App\Services\SongStorages\Concerns\DeletesUsingFilesystem;
use App\Values\UploadReference;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DropboxStorage extends CloudStorage
{
    use DeletesUsingFilesystem;

    public function __construct(
        private readonly DropboxFilesystem $filesystem,
        private readonly array $config
    ) {
        $this->filesystem->getAdapter()->getClient()->setAccessToken($this->maybeRefreshAccessToken());
    }

    public function storeUploadedFile(string $uploadedFilePath, User $uploader): UploadReference
    {
        $key = $this->generateStorageKey(basename($uploadedFilePath), $uploader);
        $this->uploadToStorage($key, $uploadedFilePath);

        return UploadReference::make(
            location: "dropbox://$key",
            localPath: $uploadedFilePath,
        );
    }

    public function undoUpload(UploadReference $reference): void
    {
        // Delete the temporary file
        File::delete($reference->localPath);

        // Delete the file from Dropbox
        $this->delete(Str::after($reference->location, 'dropbox://'));
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
        $this->filesystem->writeStream($key, fopen($path, 'r'));
    }

    public function deleteFileWithKey(string $key, bool $backup): void
    {
        $this->deleteUsingFilesystem($this->filesystem, $key, $backup);
    }
}
