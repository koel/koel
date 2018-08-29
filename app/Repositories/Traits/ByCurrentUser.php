<?php

namespace App\Repositories\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait ByCurrentUser
{
    private function byCurrentUser(): Builder
    {
        return $this->model->whereUserId($this->auth->id());
    }

    public function getAllByCurrentUser(): Collection
    {
        return $this->byCurrentUser()->get();
    }
}
