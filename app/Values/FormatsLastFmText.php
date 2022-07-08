<?php

namespace App\Values;

trait FormatsLastFmText
{
    /**
     * Correctly format a value returned by Last.fm.
     */
    private static function formatLastFmText(?string $value): string
    {
        return $value
            ? trim(str_replace('Read more on Last.fm', '', nl2br(strip_tags(html_entity_decode($value)))))
            : '';
    }
}
