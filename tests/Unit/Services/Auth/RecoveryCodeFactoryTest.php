<?php

namespace Tests\Unit\Services\Auth;

use App\Services\Auth\RecoveryCodeFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Webmozart\Assert\InvalidArgumentException;

class RecoveryCodeFactoryTest extends TestCase
{
    private RecoveryCodeFactory $factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->factory = new RecoveryCodeFactory();
    }

    #[Test]
    public function generateCodesReturnsRequestedCount(): void
    {
        self::assertCount(8, $this->factory->generateCodes(8));
        self::assertCount(1, $this->factory->generateCodes(1));
    }

    #[Test]
    public function generatedCodesAreUppercaseStrings(): void
    {
        foreach ($this->factory->generateCodes(5) as $code) {
            self::assertMatchesRegularExpression('/^[A-Z0-9 ]+$/', $code);
            self::assertNotSame('', $code);
        }
    }

    #[Test]
    public function generateCodesRejectsZeroCount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->factory->generateCodes(0);
    }

    #[Test]
    public function generateCodesRejectsNegativeCount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->factory->generateCodes(-1);
    }
}
