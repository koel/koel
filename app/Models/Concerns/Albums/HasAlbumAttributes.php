<?php

namespace App\Models\Concerns\Albums;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

trait HasAlbumAttributes
{
    protected function isUnknown(): Attribute
    {
        return Attribute::get(fn (): bool => $this->name === self::UNKNOWN_NAME);
    }

    /**
     * Sometimes the tags extracted from getID3 are HTML entity encoded.
     * This makes sure they are always sane.
     */
    protected function name(): Attribute
    {
        return Attribute::get(static fn (?string $value) => html_entity_decode($value))->shouldCache();
    }

    protected function thumbnail(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (!$this->cover) {
                return null;
            }

            return sprintf('%s_thumb.%s', Str::beforeLast($this->cover, '.'), Str::afterLast($this->cover, '.'));
        })->shouldCache();
    }

    /** @deprecated Only here for backward compat with mobile apps */
    protected function isCompilation(): Attribute
    {
        return Attribute::get(fn () => $this->artist->is_various);
    }
}
