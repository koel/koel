<?php

namespace App\Helpers;

class QueryStringParser
{
    /**
     * Parse a query string into key → list of values, preserving duplicates that
     * PHP's parse_str would collapse to last-wins. Keys ending in `[]` are
     * skipped — those are the standard PHP/Symfony array notation and need no
     * special handling.
     *
     * @return array<string, list<string>>
     */
    public static function parse(string $queryString): array
    {
        if ($queryString === '') {
            return [];
        }

        $result = [];

        foreach (explode('&', $queryString) as $pair) {
            if (!str_contains($pair, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $pair, 2);
            $key = urldecode($key);

            if (str_ends_with($key, '[]')) {
                continue;
            }

            $result[$key][] = urldecode($value);
        }

        return $result;
    }
}
