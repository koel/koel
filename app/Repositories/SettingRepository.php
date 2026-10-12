<?php

namespace App\Repositories;

use App\Models\Setting;

/** @extends Repository<Setting> */
class SettingRepository extends Repository
{
    /** @return array<mixed> */
    public function getInstallWideAsKeyValueArray(): array
    {
        return $this->modelClass::query()->whereNull('organization_id')->pluck('value', 'key')->toArray();
    }
}
