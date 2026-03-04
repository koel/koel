<?php

namespace App\Models\Concerns\Artists;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasArtistAttributes
{
    protected function isUnknown(): Attribute
    {
        return Attribute::get(fn (): bool => $this->name === self::UNKNOWN_NAME);
    }

    protected function isVarious(): Attribute
    {
        return Attribute::get(fn (): bool => $this->name === self::VARIOUS_NAME);
    }

    /**
     * Sometimes the tags extracted from getID3 are HTML entity encoded.
     * This makes sure they are always sane.
     */
    protected function name(): Attribute
    {
        return Attribute::get(static fn (string $value): string => html_entity_decode($value) ?: self::UNKNOWN_NAME);
    }
}
