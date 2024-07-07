<?php

namespace App\Http\Integrations\Lastfm\Concerns;

trait FormatsLastFmText
{
    /**
     * Correctly format a value returned by Last.fm.
     */
    private static function formatLastFmText(?string $value): string
    {
        $artifacts = [
            'Read more on Last.fm.',
            'Read more on Last.fm',
            'User-contributed text is available under the Creative Commons By-SA License; additional terms may apply.',
        ];

        return $value
            ? trim(str_replace($artifacts, '', nl2br(strip_tags(html_entity_decode($value)))))
            : '';
    }
}
