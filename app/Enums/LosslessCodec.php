<?php

namespace App\Enums;

enum LosslessCodec: string
{
    case FLAC = 'flac';
    case ALAC = 'alac';
    case WAV = 'wav';
    case AIFF = 'aiff';

    /** @return list<string> */
    public function mimeTypes(): array
    {
        return match ($this) {
            self::FLAC => ['audio/flac', 'audio/x-flac'],
            self::ALAC => ['audio/x-m4a'],
            self::WAV => ['audio/vnd.wave', 'audio/x-wav'],
            self::AIFF => ['audio/aiff', 'audio/x-aiff'],
        };
    }
}
