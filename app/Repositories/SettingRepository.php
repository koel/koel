<?php

namespace App\Repositories;

class SettingRepository extends AbstractRepository
{
    /** @return array<mixed> */
    public function getAllAsKeyValueArray(): array
    {
        return $this->model->pluck('value', 'key')->all();
    }
}
