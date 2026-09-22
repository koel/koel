<?php

namespace App\Hooks;

use Closure;

class FilterRegistry
{
    /** @var array<string, array<Closure>> */
    private array $filters = [];

    public function listen(Filter $filter, Closure $callback): void
    {
        $this->filters[$filter->value][] = $callback;
    }

    /**
     * @template T
     *
     * @param T $payload
     *
     * @return T
     */
    public function filter(Filter $filter, mixed $payload): mixed
    {
        foreach ($this->filters[$filter->value] ?? [] as $callback) {
            $payload = $callback($payload);
        }

        return $payload;
    }

    public function forget(Filter $filter): void
    {
        unset($this->filters[$filter->value]);
    }
}
