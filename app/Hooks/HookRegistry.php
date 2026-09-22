<?php

namespace App\Hooks;

use App\Values\HookHandle;
use Closure;

class HookRegistry
{
    private const int DEFAULT_PRIORITY = 10;

    /** @var array<string, array<int, array<int, Closure>>> */
    private array $actions = [];

    /** @var array<string, array<int, array<int, Closure>>> */
    private array $filters = [];

    private int $lastId = 0;

    public function addAction(Action $action, Closure $callback, int $priority = self::DEFAULT_PRIORITY): HookHandle
    {
        $handle = HookHandle::make($action, $priority, ++$this->lastId);

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
        $handle = HookHandle::make($filter, $priority, ++$this->lastId);

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
            unset($this->actions[$handle->hook->value][$handle->priority][$handle->id]);

            return;
        }

        unset($this->filters[$handle->hook->value][$handle->priority][$handle->id]);
    }
}
