<?php

namespace App\Repositories;

use App\Models\Setting;

class SettingRepository extends Repository
{
    /** @return array */
    public function getAllAsKeyValueArray(): array
    {
        return $this->model->pluck('value', 'key')->toArray();
    }

    public function getByKey(string $key): mixed
    {
        return Setting::get($key);
    }

    public function guessModelClass(): string
    {
        return Setting::class;
    }
}
