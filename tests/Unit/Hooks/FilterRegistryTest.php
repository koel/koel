<?php

namespace Tests\Unit\Hooks;

use App\Hooks\Filter;
use App\Hooks\FilterRegistry;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FilterRegistryTest extends TestCase
{
    private FilterRegistry $registry;

    public function setUp(): void
    {
        parent::setUp();

        $this->registry = new FilterRegistry();
    }

    #[Test]
    public function returnThePayloadUntouchedWhenNobodyIsListening(): void
    {
        self::assertSame(['foo' => 'bar'], $this->registry->filter(Filter::INITIAL_DATA_FETCHED, ['foo' => 'bar']));
    }

    #[Test]
    public function letAListenerChangeThePayload(): void
    {
        $this->registry->listen(Filter::INITIAL_DATA_FETCHED, static fn (array $data): array => array_merge($data, [
            'added' => true,
        ]));

        self::assertSame(
            ['foo' => 'bar', 'added' => true],
            $this->registry->filter(Filter::INITIAL_DATA_FETCHED, ['foo' => 'bar']),
        );
    }

    #[Test]
    public function passThePayloadThroughEveryListenerInTurn(): void
    {
        $this->registry->listen(Filter::INITIAL_DATA_FETCHED, static fn (array $data): array => $data + ['first' => 1]);
        $this->registry->listen(
            Filter::INITIAL_DATA_FETCHED,
            static fn (array $data): array => $data + ['second' => $data['first'] + 1],
        );

        self::assertSame(['first' => 1, 'second' => 2], $this->registry->filter(Filter::INITIAL_DATA_FETCHED, []));
    }

    #[Test]
    public function forgetEveryListenerForAFilter(): void
    {
        $this->registry->listen(Filter::INITIAL_DATA_FETCHED, static fn (array $data): array => ['replaced' => true]);
        $this->registry->forget(Filter::INITIAL_DATA_FETCHED);

        self::assertSame(['kept' => true], $this->registry->filter(Filter::INITIAL_DATA_FETCHED, ['kept' => true]));
    }
}
