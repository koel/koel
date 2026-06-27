<?php

namespace App\Helpers;

use App\Helpers\Encoding\TagFixer;
use Illuminate\Support\Arr;

class SyncedLyricsConverter
{
    /**
     * Convert SYLT (synchronised lyrics) frames from an ID3v2 tag into LRC-formatted text.
     * Returns an empty string when there are no usable synced lyrics.
     *
     * @param array<mixed> $frames
     */
    public static function fromSyltFrames(array $frames): string
    {
        // A tag may carry several SYLT frames (different languages/descriptors); use the first usable one.
        foreach ($frames as $frame) {
            // Format 2 is "milliseconds from beginning of file"; MPEG-frame timestamps (1) can't be converted reliably.
            if (!is_array($frame) || (int) Arr::get($frame, 'timestampformat') !== 2) {
                continue;
            }

            $lines = self::entriesToLrcLines(Arr::wrap(Arr::get($frame, 'lyrics', [])));

            if ($lines) {
                return implode("\n", $lines);
            }
        }

        return '';
    }

    /**
     * @param array<mixed> $entries
     * @return list<string>
     */
    private static function entriesToLrcLines(array $entries): array
    {
        $lines = [];
        $firstEntrySeen = false;

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $timestamp = Arr::get($entry, 'timestamp');

            // Only the first entry may legitimately omit its timestamp (it marks the start of the file);
            // a later entry without one is malformed, so skip it rather than placing it at the beginning.
            if ($timestamp === null && $firstEntrySeen) {
                continue;
            }

            $firstEntrySeen = true;

            $text = trim(html_entity_decode(TagFixer::fix((string) Arr::get($entry, 'data', ''))));

            if ($text === '') {
                continue;
            }

            $lines[] = self::formatTimestamp((int) $timestamp) . $text;
        }

        return $lines;
    }

    private static function formatTimestamp(int $milliseconds): string
    {
        $centiseconds = intdiv(max($milliseconds, 0), 10);

        return sprintf(
            '[%02d:%02d.%02d]',
            intdiv($centiseconds, 6000),
            intdiv($centiseconds % 6000, 100),
            $centiseconds % 100,
        );
    }
}
