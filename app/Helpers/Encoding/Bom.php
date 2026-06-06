<?php

namespace App\Helpers\Encoding;

use Illuminate\Support\Str;

/**
 * Remove a leading byte-order mark and normalize the result to UTF-8.
 *
 * Used at boundaries where untrusted text crosses into koel — e.g. artist names
 * extracted from ID3 tags or uploaded filenames. Without stripping, a stray BOM
 * lands as a literal U+FEFF in the database and breaks equality lookups
 * ("Beethoven" !== "\u{FEFF}Beethoven").
 */
final class Bom
{
    /**
     * ORDER MATTERS: 4-byte BOMs are tested before 2-byte BOMs so that UTF-32LE
     * (\xFF\xFE\x00\x00) is not misidentified as UTF-16LE (\xFF\xFE).
     */
    private const BOMS = [
        "\x00\x00\xFE\xFF" => 'UTF-32BE',
        "\xFF\xFE\x00\x00" => 'UTF-32LE',
        "\xFE\xFF" => 'UTF-16BE',
        "\xFF\xFE" => 'UTF-16LE',
        "\xEF\xBB\xBF" => 'UTF-8',
    ];

    public static function strip(?string $str): ?string
    {
        if ($str === null || $str === '') {
            return $str;
        }

        foreach (self::BOMS as $bom => $encoding) {
            if (!Str::startsWith($str, $bom)) {
                continue;
            }

            $rest = substr($str, strlen($bom));

            return $encoding === 'UTF-8' ? $rest : mb_convert_encoding($rest, 'UTF-8', $encoding);
        }

        return $str;
    }
}
