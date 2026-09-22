<?php

namespace App\Hooks;

use Closure;

class HookRegistry
{
    /** @var array<string, array<Closure>> */
    private array $filters = [];

    public function listen(Hook $hook, Closure $callback): void
    {
        $this->filters[$hook->value][] = $callback;
    }

    /**
     * @template T
     *
     * @param T $payload
     *
     * @return T
     */
    public function filter(Hook $hook, mixed $payload): mixed
    {
        foreach ($this->filters[$hook->value] ?? [] as $callback) {
            $payload = $callback($payload);
        }

        return $payload;
    }

    public function forget(Hook $hook): void
    {
        unset($this->filters[$hook->value]);
    }
}
