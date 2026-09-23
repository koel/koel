<?php

namespace App\Hooks;

use App\Values\HookHandle;
use Closure;

class HookRegistry
{
    private const int DEFAULT_PRIORITY = 10;

    /** @var array<string, array<int, array<string, Closure>>> */
    private array $actions = [];

    /** @var array<string, array<int, array<string, Closure>>> */
    private array $filters = [];

    public function addAction(string $action, Closure $callback, int $priority = self::DEFAULT_PRIORITY): HookHandle
    {
        $handle = HookHandle::make($action);

        $this->actions[$action][$priority][$handle->id] = $callback;
        ksort($this->actions[$action]);

        return $handle;
    }

    public function doAction(string $action, mixed ...$args): void
    {
        foreach ($this->actions[$action] ?? [] as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }

    public function addFilter(string $filter, Closure $callback, int $priority = self::DEFAULT_PRIORITY): HookHandle
    {
        $handle = HookHandle::make($filter);

        $this->filters[$filter][$priority][$handle->id] = $callback;
        ksort($this->filters[$filter]);

        return $handle;
    }

    /**
     * @template T
     *
     * @param T $value
     *
     * @return T
     */
    public function applyFilters(string $filter, mixed $value, mixed ...$args): mixed
    {
        foreach ($this->filters[$filter] ?? [] as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback($value, ...$args);
            }
        }

        return $value;
    }

    public function removeAction(HookHandle $handle): void
    {
        $this->actions = self::without($this->actions, $handle);
    }

    public function removeFilter(HookHandle $handle): void
    {
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
}
