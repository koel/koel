<?php

namespace App\Hooks;

use App\Enums\Hooks\Action;
use App\Enums\Hooks\Filter;
use App\Values\HookHandle;
use BackedEnum;
use Closure;

class HookRegistry
{
    private const int DEFAULT_PRIORITY = 10;

    /** @var array<string, array<int, array<string, Closure>>> */
    private array $actions = [];

    /** @var array<string, array<int, array<string, Closure>>> */
    private array $filters = [];

    public function addAction(
        Action|string $action,
        Closure $callback,
        int $priority = self::DEFAULT_PRIORITY,
    ): HookHandle {
        $handle = HookHandle::make(self::nameOf($action));

        $this->actions[$handle->hook][$priority][$handle->id] = $callback;
        ksort($this->actions[$handle->hook]);

        return $handle;
    }

    public function doAction(Action|string $action, mixed ...$args): void
    {
        foreach ($this->actions[self::nameOf($action)] ?? [] as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }

    public function addFilter(
        Filter|string $filter,
        Closure $callback,
        int $priority = self::DEFAULT_PRIORITY,
    ): HookHandle {
        $handle = HookHandle::make(self::nameOf($filter));

        $this->filters[$handle->hook][$priority][$handle->id] = $callback;
        ksort($this->filters[$handle->hook]);

        return $handle;
    }

    /**
     * @template T
     *
     * @param T $value
     *
     * @return T
     */
    public function applyFilters(Filter|string $filter, mixed $value, mixed ...$args): mixed
    {
        foreach ($this->filters[self::nameOf($filter)] ?? [] as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback($value, ...$args);
            }
        }

        return $value;
    }

    public function remove(HookHandle $handle): void
    {
        $this->actions = self::without($this->actions, $handle);
        $this->filters = self::without($this->filters, $handle);
    }

    /**
     * @param array<string, array<int, array<string, Closure>>> $callbacks
     *
     * @return array<string, array<int, array<string, Closure>>>
     */
    private static function without(array $callbacks, HookHandle $handle): array
    {
        foreach (array_keys($callbacks[$handle->hook] ?? []) as $priority) {
            unset($callbacks[$handle->hook][$priority][$handle->id]);
        }

        return $callbacks;
    }

    private static function nameOf(Action|Filter|string $hook): string
    {
        return $hook instanceof BackedEnum ? $hook->value : $hook;
    }
}
