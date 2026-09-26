<?php

namespace Tests\Fakes;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Engines\NullEngine;

/** A Scout engine that records which models it was asked to index and does nothing else. */
class RecordingSearchEngine extends NullEngine
{
    /** @var array<string, array<int, string>> Model class => keys passed to update() */
    public array $updated = [];

    public function update($models): void
    {
        /** @var Collection<int, Model> $models */
        foreach ($models as $model) {
            $this->updated[$model::class][] = (string) $model->getScoutKey(); // @phpstan-ignore-line
        }
    }

    /** @return array<int, string> */
    public function updatedKeysOf(string $class): array
    {
        return array_values(array_unique($this->updated[$class] ?? []));
    }
}
