<?php

namespace Tests\Unit\Services;

use App\Services\RichTextSanitizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RichTextSanitizerTest extends TestCase
{
    private RichTextSanitizer $sanitizer;

    public function setUp(): void
    {
        parent::setUp();

        $this->sanitizer = new RichTextSanitizer();
    }

    #[Test]
    public function keepTheMarkupTheEditorOffers(): void
    {
        $html =
            '<h2>Story</h2><p><strong>Bold</strong> <em>italic</em> <u>under</u> <s>gone</s></p>'
            . '<ul><li>One</li></ul><ol><li>Two</li></ol><p><a href="https://koel.dev">Koel</a></p>';

        self::assertSame($html, $this->sanitizer->sanitize($html));
    }

    #[Test]
    public function stripEverythingElse(): void
    {
        $sanitized = $this->sanitizer->sanitize(
            '<p style="color:red" onclick="steal()">Hi<script>steal()</script></p>'
            . '<img src="x.png"><a href="javascript:steal()">Click</a><table><tr><td>Cell</td></tr></table>',
        );

        self::assertSame('<p>Hi</p><a>Click</a>Cell', $sanitized);
    }

    #[Test]
    public function returnNullWhenNothingReadableIsLeft(): void
    {
        self::assertNull($this->sanitizer->sanitize('<p> </p><p><br></p>'));
    }
}
