<?php

namespace App\Models\Concerns\Songs;

use App\Enums\PlayableType;
use App\Values\SongStorageMetadata\SongStorageMetadata;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\File;
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
        return (new Attribute(get: fn (): SongStorageMetadata => SongStorageMetadata::fromLocation(
            $this->storage,
            $this->path,
        )))->shouldCache();
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
        return Attribute::get(fn () => $this->genres->pluck('name')->sort()->implode(', '))->shouldCache();
    }
}
