<?php

namespace App\Helpers;

use Illuminate\Support\Str;

/**
 * Builds the value songs are sorted by when sorting by title: "The Song" becomes "Song, The".
 */
class SortTitle
{
    public const int MAX_LENGTH = 191;

    private const string LEADING_ARTICLE_PATTERN = '/^(the|an?)\s+(.+)$/iu';

    public static function fromTitle(string $title): string
    {
        $title = Str::trim($title);

        $sortTitle = preg_match(self::LEADING_ARTICLE_PATTERN, $title, $matches)
            ? sprintf('%s, %s', Str::ucfirst($matches[2]), $matches[1])
            : $title;

        return self::truncate($sortTitle);
    }

    public static function truncate(string $sortTitle): string
    {
        return Str::substr(Str::trim($sortTitle), 0, self::MAX_LENGTH);
    }
}
