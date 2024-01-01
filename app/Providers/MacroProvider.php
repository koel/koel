<?php

namespace App\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class MacroProvider extends ServiceProvider
{
    public function boot(): void
    {
        Collection::macro('orderByArray', function (array $orderBy, string $key = 'id'): Collection {
            /** @var Collection $this */
            return $this->sortBy(static fn ($item) => array_search($item->$key, $orderBy, true));
        });
    }
}
