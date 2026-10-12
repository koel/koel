<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class LuceneQuery
{
    /**
     * Quote a value so the search engine matches it as one phrase. Without the quotes, a multi-word
     * value is read as separate terms and a name nobody has heard of still matches something: searching
     * MusicBrainz for `artist:Zzzqqxyw Nonexistent Bandname` returns an artist called "Bandname", with
     * full confidence, where the quoted form correctly returns nothing.
     */
    public static function phrase(string $value): string
    {
        return '"' . Str::replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
    }
}
