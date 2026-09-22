<?php

namespace App\Hooks;

use App\Enums\Hooks\Action;
use App\Enums\Hooks\Filter;
use App\Values\HookHandle;
use Closure;

class HookRegistry
{
    private const int DEFAULT_PRIORITY = 10;

    /** @var array<string, array<int, array<string, Closure>>> */
    private array $actions = [];

    /** @var array<string, array<int, array<string, Closure>>> */
    private array $filters = [];

    public function addAction(Action $action, Closure $callback, int $priority = self::DEFAULT_PRIORITY): HookHandle
    {
        $handle = HookHandle::make($action);

        $this->actions[$action->value][$priority][$handle->id] = $callback;
        ksort($this->actions[$action->value]);

        return $handle;
    }

    public function doAction(Action $action, mixed ...$args): void
    {
        foreach ($this->actions[$action->value] ?? [] as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }

    public function addFilter(Filter $filter, Closure $callback, int $priority = self::DEFAULT_PRIORITY): HookHandle
    {
        $handle = HookHandle::make($filter);

        $this->filters[$filter->value][$priority][$handle->id] = $callback;
        ksort($this->filters[$filter->value]);

        return $handle;
    }

    /**
     * @template T
     *
     * @param T $value
     *
     * @return T
     */
    public function applyFilters(Filter $filter, mixed $value, mixed ...$args): mixed
    {
        foreach ($this->filters[$filter->value] ?? [] as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback($value, ...$args);
            }
        }

        return $value;
    }

    public function remove(HookHandle $handle): void
    {
        if ($handle->hook instanceof Action) {
            $this->actions = self::without($this->actions, $handle);

            return;
        }

        $this->filters = self::without($this->filters, $handle);
    }

    /**
     * @param array<string, array<int, array<string, Closure>>> $callbacks
     *
     * @return array<string, array<int, array<string, Closure>>>
     */
    private static function without(array $callbacks, HookHandle $handle): array
    {
        foreach (array_keys($callbacks[$handle->hook->value] ?? []) as $priority) {
            unset($callbacks[$handle->hook->value][$priority][$handle->id]);
        }

        return $callbacks;
    }
}
