<?php

namespace App\Services;

class Util
{
    public function __construct()
    {
        defined('UTF8_BOM') || define('UTF8_BOM', chr(0xEF).chr(0xBB).chr(0xBF));
        defined('UTF16_LITTLE_ENDIAN_BOM') || define('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF).chr(0xFE));
        defined('UTF16_BIG_ENDIAN_BOM') || define('UTF16_BIG_ENDIAN_BOM', chr(0xFE).chr(0xFF));
        defined('UTF32_LITTLE_ENDIAN_BOM') || define('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF).chr(0xFE).chr(0x00).chr(0x00));
        defined('UTF32_BIG_ENDIAN_BOM') || define('UTF32_BIG_ENDIAN_BOM', chr(0x00).chr(0x00).chr(0xFE).chr(0xFF));
    }

    /**
     * Detects higher UTF encoded strings.
     *
     * @param string $str
     *
     * @return string|false
     */
    public function detectUTFEncoding($str)
    {
        switch (substr($str, 0, 2)) {
            case UTF16_BIG_ENDIAN_BOM:
                return 'UTF-16BE';
            case UTF16_LITTLE_ENDIAN_BOM:
                return 'UTF-16LE';
        }

        switch (substr($str, 0, 3)) {
            case UTF8_BOM:
                return 'UTF-8';
        }

        switch (substr($str, 0, 4)) {
            case UTF32_BIG_ENDIAN_BOM:
                return 'UTF-32BE';
            case UTF32_LITTLE_ENDIAN_BOM:
                return 'UTF-32LE';
        }

        return false;
    }
}
