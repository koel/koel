<?php

namespace Tests\Unit\Values;

use App\Enums\ImageChangeType;
use App\Values\ImageChange;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImageChangeTest extends TestCase
{
    #[Test]
    public function keepLeavesTheAttributeOut(): void
    {
        $attributes = ImageChange::make(ImageChangeType::KEEP)->applyToAttributes([
            'name' => 'Foo',
            'cover' => 'dummy-src',
        ], 'cover');

        self::assertSame(['name' => 'Foo'], $attributes);
    }

    #[Test]
    public function removeEmptiesTheAttribute(): void
    {
        $attributes = ImageChange::make(ImageChangeType::REMOVE)->applyToAttributes(['name' => 'Foo'], 'cover');

        self::assertSame(['name' => 'Foo', 'cover' => ''], $attributes);
    }

    #[Test]
    public function replaceSetsTheNewFileName(): void
    {
        $attributes = ImageChange::make(ImageChangeType::REPLACE, 'new.webp')->applyToAttributes([
            'name' => 'Foo',
            'cover' => 'dummy-src',
        ], 'cover');

        self::assertSame(['name' => 'Foo', 'cover' => 'new.webp'], $attributes);
    }
}
