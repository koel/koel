<?php

namespace App\Enums;

enum NativelyPlayableLosslessFormat: string
{
    case FLAC = 'flac';
    case ALAC = 'alac';
    case WAV = 'wav';
    case AIFF = 'aiff';

    /** @return list<string> */
    public function mimeTypes(): array
    {
        return config("koel.streaming.natively_playable_lossless_mime_types.{$this->value}", []);
    }
}
