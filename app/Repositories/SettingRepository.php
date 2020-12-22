<?php

namespace App\Repositories;

use App\Models\Setting;

class SettingRepository extends AbstractRepository
{
    public function getModelClass(): string
    {
        return Setting::class;
    }

    /** @return array<mixed> */
    public function getAllAsKeyValueArray(): array
    {
        return $this->model->pluck('value', 'key')->all();
    }
}
