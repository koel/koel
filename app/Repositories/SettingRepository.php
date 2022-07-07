<?php

namespace App\Repositories;

use App\Models\Setting;

class SettingRepository extends AbstractRepository
{
    /** @return array<mixed> */
    public function getAllAsKeyValueArray(): array
    {
        return $this->model->pluck('value', 'key')->toArray();
    }

    public function getByKey(string $key): mixed
    {
        return Setting::get($key);
    }
}
