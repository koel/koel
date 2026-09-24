<?php

namespace Tests\Unit\Hooks;

use App\Facades\Hooks;
use App\Hooks\Action;
use App\Hooks\Filter;
use App\Hooks\HookRegistry;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HookHelpersTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Hooks::swap(new HookRegistry());
    }

    #[Test]
    public function filterThroughTheHelpers(): void
    {
        add_filter(Filter::INITIAL_DATA_FETCHED, static fn (array $data): array => $data + ['added' => true]);

        self::assertSame(['added' => true], apply_filters(Filter::INITIAL_DATA_FETCHED, []));
    }

    #[Test]
    public function actThroughTheHelpers(): void
    {
        $calls = 0;

        add_action(Action::APPLICATION_BOOTED, static function () use (&$calls): void {
            $calls++;
        });

        do_action(Action::APPLICATION_BOOTED);

        self::assertSame(1, $calls);
    }

    #[Test]
    public function removeAFilterThroughTheHelpers(): void
    {
        $handle = add_filter(Filter::INITIAL_DATA_FETCHED, static fn (array $data): array => ['replaced' => true]);
        remove_filter($handle);

        self::assertSame(['kept' => true], apply_filters(Filter::INITIAL_DATA_FETCHED, ['kept' => true]));
    }

    #[Test]
    public function removeAnActionThroughTheHelpers(): void
    {
        $calls = 0;

        $handle = add_action(Action::APPLICATION_BOOTED, static function () use (&$calls): void {
            $calls++;
        });

        remove_action($handle);
        do_action(Action::APPLICATION_BOOTED);

        self::assertSame(0, $calls);
    }
}
