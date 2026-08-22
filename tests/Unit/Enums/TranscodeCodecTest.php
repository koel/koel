<?php

namespace Tests\Unit\Enums;

use App\Enums\TranscodeCodec;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TranscodeCodecTest extends TestCase
{
    #[Test]
    public function providesContainerMetadata(): void
    {
        self::assertSame('m4a', TranscodeCodec::AAC->extension());
        self::assertSame('audio/mp4', TranscodeCodec::AAC->mimeType());
        self::assertSame('weba', TranscodeCodec::OPUS->extension());
        self::assertSame('audio/webm', TranscodeCodec::OPUS->mimeType());
    }

    #[Test]
    public function resolvesFromExtension(): void
    {
        self::assertSame(TranscodeCodec::OPUS, TranscodeCodec::fromExtension('weba'));
        self::assertSame(TranscodeCodec::OPUS, TranscodeCodec::fromExtension('WEBA'));
        self::assertSame(TranscodeCodec::AAC, TranscodeCodec::fromExtension('m4a'));
        self::assertSame(TranscodeCodec::AAC, TranscodeCodec::fromExtension('mp4'));
    }
}
