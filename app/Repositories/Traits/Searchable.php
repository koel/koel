<?php

namespace App\Repositories\Traits;

use Laravel\Scout\Builder;

trait Searchable
{
    public function search(string $keywords): Builder
    {
        return forward_static_call([$this->getModelClass(), 'search'], $keywords);
    }
}
