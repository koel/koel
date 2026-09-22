<?php

namespace Tests\Unit\Hooks;

use App\Hooks\Action;
use App\Hooks\Filter;
use App\Hooks\HookRegistry;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HookRegistryTest extends TestCase
{
    private HookRegistry $registry;

    public function setUp(): void
    {
        parent::setUp();

        $this->registry = new HookRegistry();
    }

    #[Test]
    public function returnTheValueUntouchedWhenNobodyFilters(): void
    {
        self::assertSame(
            ['foo' => 'bar'],
            $this->registry->applyFilters(Filter::INITIAL_DATA_FETCHED, ['foo' => 'bar']),
        );
    }

    #[Test]
    public function letAFilterChangeTheValue(): void
    {
        $this->registry->addFilter(Filter::INITIAL_DATA_FETCHED, static fn (array $data): array => array_merge($data, [
            'added' => true,
        ]));

        self::assertSame(
            ['foo' => 'bar', 'added' => true],
            $this->registry->applyFilters(Filter::INITIAL_DATA_FETCHED, ['foo' => 'bar']),
        );
    }

    #[Test]
    public function handEachFilterWhatThePreviousOneReturned(): void
    {
        $this->registry->addFilter(
            Filter::INITIAL_DATA_FETCHED,
            static fn (array $data): array => $data + ['first' => 1],
        );
        $this->registry->addFilter(
            Filter::INITIAL_DATA_FETCHED,
            static fn (array $data): array => $data + ['second' => $data['first'] + 1],
        );

        self::assertSame(
            ['first' => 1, 'second' => 2],
            $this->registry->applyFilters(Filter::INITIAL_DATA_FETCHED, []),
        );
    }

    #[Test]
    public function runFiltersInPriorityOrder(): void
    {
        $this->registry->addFilter(
            Filter::INITIAL_DATA_FETCHED,
            static fn (string $order): string => "$order late",
            20,
        );
        $this->registry->addFilter(
            Filter::INITIAL_DATA_FETCHED,
            static fn (string $order): string => "$order early",
            5,
        );

        self::assertSame('start early late', $this->registry->applyFilters(Filter::INITIAL_DATA_FETCHED, 'start'));
    }

    #[Test]
    public function passTheExtraArgumentsToEveryFilter(): void
    {
        $this->registry->addFilter(
            Filter::INITIAL_DATA_FETCHED,
            static fn (string $value, string $suffix): string => "$value $suffix",
        );

        self::assertSame('value suffix', $this->registry->applyFilters(
            Filter::INITIAL_DATA_FETCHED,
            'value',
            'suffix',
        ));
    }

    #[Test]
    public function runEveryActionInPriorityOrder(): void
    {
        $calls = [];

        $this->registry->addAction(
            Action::APPLICATION_BOOTED,
            static function () use (&$calls): void {
                $calls[] = 'late';
            },
            20,
        );

        $this->registry->addAction(
            Action::APPLICATION_BOOTED,
            static function () use (&$calls): void {
                $calls[] = 'early';
            },
            5,
        );

        $this->registry->doAction(Action::APPLICATION_BOOTED);

        self::assertSame(['early', 'late'], $calls);
    }

    #[Test]
    public function passTheArgumentsToEveryAction(): void
    {
        $received = null;

        $this->registry->addAction(Action::APPLICATION_BOOTED, static function (string $what) use (&$received): void {
            $received = $what;
        });

        $this->registry->doAction(Action::APPLICATION_BOOTED, 'booted');

        self::assertSame('booted', $received);
    }

    #[Test]
    public function doNothingWhenNobodyListensToAnAction(): void
    {
        $this->expectNotToPerformAssertions();

        $this->registry->doAction(Action::APPLICATION_BOOTED);
    }

    #[Test]
    public function removeOneFilterAndLeaveTheOthers(): void
    {
        $handle = $this->registry->addFilter(
            Filter::INITIAL_DATA_FETCHED,
            static fn (array $data): array => $data + ['removed' => true],
        );

        $this->registry->addFilter(
            Filter::INITIAL_DATA_FETCHED,
            static fn (array $data): array => $data + ['kept' => true],
        );

        $this->registry->remove($handle);

        self::assertSame(['kept' => true], $this->registry->applyFilters(Filter::INITIAL_DATA_FETCHED, []));
    }

    #[Test]
    public function removeOneActionAndLeaveTheOthers(): void
    {
        $calls = [];

        $handle = $this->registry->addAction(Action::APPLICATION_BOOTED, static function () use (&$calls): void {
            $calls[] = 'removed';
        });

        $this->registry->addAction(Action::APPLICATION_BOOTED, static function () use (&$calls): void {
            $calls[] = 'kept';
        });

        $this->registry->remove($handle);
        $this->registry->doAction(Action::APPLICATION_BOOTED);

        self::assertSame(['kept'], $calls);
    }

    #[Test]
    public function shrugAtAHandleThatWasAlreadyRemoved(): void
    {
        $handle = $this->registry->addFilter(Filter::INITIAL_DATA_FETCHED, static fn (array $data): array => $data);

        $this->registry->remove($handle);
        $this->registry->remove($handle);

        self::assertSame(
            ['kept' => true],
            $this->registry->applyFilters(Filter::INITIAL_DATA_FETCHED, ['kept' => true]),
        );
    }
}
