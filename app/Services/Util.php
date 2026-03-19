<?php

namespace App\Services;

class Util
{
    public function __construct()
    {
        defined('UTF8_BOM') || define('UTF8_BOM', chr(0xEF) . chr(0xBB) . chr(0xBF));
        defined('UTF16_LITTLE_ENDIAN_BOM') || define('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE));
        defined('UTF16_BIG_ENDIAN_BOM') || define('UTF16_BIG_ENDIAN_BOM', chr(0xFE) . chr(0xFF));
        defined('UTF32_LITTLE_ENDIAN_BOM')
            || define('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00));
        defined('UTF32_BIG_ENDIAN_BOM')
            || define('UTF32_BIG_ENDIAN_BOM', chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF));
    }

    public function detectUTFEncoding(?string $str): ?string
    {
        return match (true) {
            substr($str, 0, 2) === UTF16_BIG_ENDIAN_BOM => 'UTF-16BE',
            substr($str, 0, 2) === UTF16_LITTLE_ENDIAN_BOM => 'UTF-16LE',
            substr($str, 0, 3) === UTF8_BOM => 'UTF-8',
            substr($str, 0, 4) === UTF32_BIG_ENDIAN_BOM => 'UTF-32BE',
            substr($str, 0, 4) === UTF32_LITTLE_ENDIAN_BOM => 'UTF-32LE',
            default => null,
        };
    }
}
