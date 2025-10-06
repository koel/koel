<?php

namespace Tests\Unit\Services;

use App\Services\SvgSanitizer;
use enshrined\svgSanitize\Sanitizer;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SvgSanitizerTest extends TestCase
{
    private Sanitizer|MockInterface $sanitizer;
    private SvgSanitizer $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->sanitizer = Mockery::mock(Sanitizer::class);
        $this->service = new SvgSanitizer($this->sanitizer);
    }

    #[Test]
    public function sanitize(): void
    {
        $this->sanitizer
            ->expects('sanitize')
            ->with('<svg><raw /></svg>')
            ->andReturn('<svg><sanitized /></svg>');

        self::assertSame('<svg><sanitized /></svg>', $this->service->sanitize('<svg><raw /></svg>'));
    }
}
