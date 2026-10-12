<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property int $id
 * @property ?string $organization_id
 * @property string $key
 * @property mixed $value
 *
 * @method static SettingFactory factory(...$parameters)
 */
#[Table(timestamps: false)]
#[Unguarded]
class Setting extends Model implements AuditableContract
{
    use Auditable;
    use HasFactory;

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    /**
     * @param ?Organization $organization the organization the setting belongs to, or null for an install-wide one
     */
    public static function get(string $key, ?Organization $organization = null): mixed
    {
        return self::query()->where('key', $key)->where('organization_id', $organization?->id)->first()?->value;
    }

    /**
     * Set a setting (no pun) value.
     *
     * @param array|string $key the key of the setting, or an associative array of settings,
     *                            in which case $value will be discarded
     * @param ?Organization $organization the organization the setting belongs to, or null for an install-wide one
     */
    public static function set(array|string $key, $value = '', ?Organization $organization = null): void
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                self::set($k, $v, $organization);
            }

            return;
        }

        self::query()->updateOrCreate(['key' => $key, 'organization_id' => $organization?->id], compact('value'));
    }
}
