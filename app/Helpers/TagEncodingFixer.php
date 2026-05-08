<?php

namespace App\Helpers;

/**
 * Recover the UTF-8 form of an ID3 tag string that was mis-decoded somewhere upstream.
 *
 * Two failure modes are handled:
 *
 *   - getID3 returned raw, non-UTF-8 bytes (convertFromBytes path).
 *   - getID3 already converted the bytes to UTF-8 by treating the source as Latin-1,
 *     producing valid-but-meaningless UTF-8 — the "double mojibake" case (e.g. the
 *     GB2312 bytes for "安妮" become `°²ÄÝ`). See issue #1816.
 *
 * The fix is deliberately narrow:
 *
 *   - GB18030 covers GB2312/GBK and the most common CJK bug reports.
 *   - Windows-1252 catches Latin-1 / Western European mojibake.
 *
 * Other CJK encodings (Big5, Shift_JIS, EUC-JP, EUC-KR) are intentionally omitted —
 * their byte patterns overlap with GB18030's, so any ordering produces false
 * positives in one direction or the other. They're a future iteration once a
 * concrete bug report justifies the trade-off.
 */
class TagEncodingFixer
{
    public static function fix(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        if (!mb_check_encoding($value, 'UTF-8')) {
            return self::convertFromBytes($value);
        }

        return self::recoverFromDoubleMojibake($value);
    }

    private static function convertFromBytes(string $value): string
    {
        if (self::looksLikeCjkBytes($value) && mb_check_encoding($value, 'GB18030')) {
            return mb_convert_encoding($value, 'UTF-8', 'GB18030');
        }

        if (mb_check_encoding($value, 'Windows-1252')) {
            return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        return $value;
    }

    private static function recoverFromDoubleMojibake(string $value): string
    {
        // Reverse the bogus Latin-1 → UTF-8 step. //IGNORE drops codepoints that
        // don't fit in Latin-1; if any are dropped, the source was real UTF-8 with
        // non-Latin-1 codepoints and we leave it alone.
        // @mago-expect lint:no-error-control-operator -- iconv emits an E_NOTICE when the source
        //                                                   contains codepoints outside Latin-1.
        //                                                   //IGNORE is the documented way to skip
        //                                                   them; the notice is the noise we silence.
        $bytes = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $value);

        if ($bytes === false || mb_strlen($value) !== strlen($bytes)) {
            return $value;
        }

        if (self::looksLikeCjkBytes($bytes) && mb_check_encoding($bytes, 'GB18030')) {
            return mb_convert_encoding($bytes, 'UTF-8', 'GB18030');
        }

        return $value;
    }

    /**
     * Heuristic: true when every high byte (>= 0x80) is adjacent to another high byte
     * AND there are at least 4 such bytes total (≥ 2 CJK characters' worth). CJK byte
     * streams in GB18030 always satisfy this; Latin-1 strings with isolated accented
     * characters (Café, Renée) don't. Protects against the false-positive case where
     * a Latin-1 string's bytes happen to be a structurally-valid GB18030 sequence.
     */
    private static function looksLikeCjkBytes(string $bytes): bool
    {
        $length = strlen($bytes);
        $highCount = 0;

        for ($i = 0; $i < $length; $i++) {
            if (ord($bytes[$i]) < 0x80) {
                continue;
            }

            if (($i + 1) >= $length || ord($bytes[$i + 1]) < 0x80) {
                return false;
            }

            $highCount += 2;
            $i++;
        }

        return $highCount >= 4;
    }
}
