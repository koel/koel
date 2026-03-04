<?php

namespace App\Models\Concerns\Songs;

use App\Enums\PlayableType;
use App\Enums\SongStorageType;
use App\Values\SongStorageMetadata\DropboxMetadata;
use App\Values\SongStorageMetadata\LocalMetadata;
use App\Values\SongStorageMetadata\S3CompatibleMetadata;
use App\Values\SongStorageMetadata\S3LambdaMetadata;
use App\Values\SongStorageMetadata\SftpMetadata;
use App\Values\SongStorageMetadata\SongStorageMetadata;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\File;
use Throwable;
use Webmozart\Assert\Assert;

trait HasSongAttributes
{
    protected function albumArtist(): Attribute
    {
        return Attribute::get(fn () => $this->album?->artist)->shouldCache();
    }

    protected function type(): Attribute
    {
        return Attribute::get(fn () => $this->podcast_id ? PlayableType::PODCAST_EPISODE : PlayableType::SONG);
    }

    protected function storageMetadata(): Attribute
    {
        return (new Attribute(get: function (): SongStorageMetadata {
            try {
                switch ($this->storage) {
                    case SongStorageType::SFTP:
                        preg_match('/^sftp:\\/\\/(.*)/', $this->path, $matches);
                        return SftpMetadata::make($matches[1]);

                    case SongStorageType::S3:
                        preg_match('/^s3:\\/\\/(.*)\\/(.*)/', $this->path, $matches);
                        return S3CompatibleMetadata::make($matches[1], $matches[2]);

                    case SongStorageType::S3_LAMBDA:
                        preg_match('/^s3:\\/\\/(.*)\\/(.*)/', $this->path, $matches);
                        return S3LambdaMetadata::make($matches[1], $matches[2]);

                    case SongStorageType::DROPBOX:
                        preg_match('/^dropbox:\\/\\/(.*)/', $this->path, $matches);
                        return DropboxMetadata::make($matches[1]);

                    default:
                        return LocalMetadata::make($this->path);
                }
            } catch (Throwable) {
                return LocalMetadata::make($this->path);
            }
        }))->shouldCache();
    }

    protected function basename(): Attribute
    {
        return Attribute::get(function () {
            Assert::eq($this->type, PlayableType::SONG);

            return File::basename($this->path);
        });
    }

    protected function genre(): Attribute
    {
        return Attribute::get(fn () => $this->genres->pluck('name')->implode(', '))->shouldCache();
    }
}
