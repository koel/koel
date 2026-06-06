<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $key
 * @property mixed $value
 *
 * @method static self find(string $key)
 * @method static SettingFactory factory(...$parameters)
 */
class Setting extends Model implements AuditableContract
{   

// NEXTCLOUD INTEGRATION - SWE PROJECT 2026
    public const NEXTCLOUD_URL = 'nextcloud_url';
    public const NEXTCLOUD_USERNAME = 'nextcloud_username';
    public const NEXTCLOUD_PASSWORD = 'nextcloud_password';

    /**
     * Get the Nextcloud configuration values.
     * This will be used by the media sync service to connect to the external share.
     */
    public static function getNextcloudConfig(): array
    {
        return [
            'url' => self::get(self::NEXTCLOUD_URL),
            'username' => self::get(self::NEXTCLOUD_USERNAME),
            'password' => self::get(self::NEXTCLOUD_PASSWORD),
        ];
    }


    use Auditable;
    use HasFactory;

    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public static function get(string $key): mixed
    {
        return self::find($key)?->value;
    }

    /**
     * Set a setting (no pun) value.
     *
     * @param array|string $key the key of the setting, or an associative array of settings,
     *                            in which case $value will be discarded
     */
    public static function set(array|string $key, $value = ''): void
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                self::set($k, $v);
            }

            return;
        }

        self::query()->updateOrCreate(compact('key'), compact('value'));
    }
}
