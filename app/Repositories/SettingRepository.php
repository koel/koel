<?php

namespace App\Repositories;

use App\Models\Setting;

/** @extends Repository<Setting> */
class SettingRepository extends Repository
{
    /** @return array<mixed> */
    public function getAllAsKeyValueArray(): array
    {
        return $this->modelClass::query()->pluck('value', 'key')->toArray();
    }

    public function getByKey(string $key): mixed
    {
        return Setting::get($key);
    }
}
