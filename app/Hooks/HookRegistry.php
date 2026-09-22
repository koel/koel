<?php

namespace App\Hooks;

use Closure;

class HookRegistry
{
    private const int DEFAULT_PRIORITY = 10;

    /** @var array<string, array<int, array<Closure>>> */
    private array $actions = [];

    /** @var array<string, array<int, array<Closure>>> */
    private array $filters = [];

    public function addAction(Action $action, Closure $callback, int $priority = self::DEFAULT_PRIORITY): void
    {
        $this->actions[$action->value][$priority][] = $callback;
        ksort($this->actions[$action->value]);
    }

    public function doAction(Action $action, mixed ...$args): void
    {
        foreach ($this->actions[$action->value] ?? [] as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }

    public function addFilter(Filter $filter, Closure $callback, int $priority = self::DEFAULT_PRIORITY): void
    {
        $this->filters[$filter->value][$priority][] = $callback;
        ksort($this->filters[$filter->value]);
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

    public function forget(Action|Filter $hook): void
    {
        unset($this->actions[$hook->value], $this->filters[$hook->value]);
    }
}
