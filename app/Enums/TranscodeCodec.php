<?php

namespace App\Enums;

enum TranscodeCodec: string
{
    case AAC = 'aac';
    case OPUS = 'opus';

    public static function default(): self
    {
        return self::AAC;
    }

    public static function fromExtension(string $extension): self
    {
        return match (strtolower($extension)) {
            'weba' => self::OPUS,
            default => self::AAC,
        };
    }

    public function extension(): string
    {
        return match ($this) {
            self::AAC => 'm4a',
            self::OPUS => 'weba',
        };
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::AAC => 'audio/mp4',
            self::OPUS => 'audio/webm',
        };
    }
}
