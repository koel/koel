<?php

namespace App\Helpers;

use Illuminate\Support\Str;

/**
 * Builds the value songs are sorted by when sorting by title: "The Song" becomes "Song, The".
 */
class SortTitle
{
    public const int MAX_LENGTH = 191;

    public const array ARTICLES = ['The', 'A', 'An', 'El', 'La', 'Los', 'Las', 'Le', 'Les'];

    public static function fromTitle(string $title): string
    {
        $title = self::removeInvisibleCharacters($title);

        $pattern = sprintf('/^(%s)\s+(.+)$/iu', implode('|', self::ARTICLES));

        $sortTitle = preg_match($pattern, $title, $matches)
            ? sprintf('%s, %s', Str::ucfirst($matches[2]), $matches[1])
            : $title;

        return self::clean($sortTitle);
    }

    public static function clean(string $sortTitle): string
    {
        return Str::substr(self::removeInvisibleCharacters($sortTitle), 0, self::MAX_LENGTH);
    }

    private static function removeInvisibleCharacters(string $text): string
    {
        return Str::trim(preg_replace('/\p{Cf}/u', '', $text));
    }
}
