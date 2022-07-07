<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $key
 * @property mixed $value
 *
 * @method static self find(string $key)
 * @method static self updateOrCreate(array $where, array $params)
 */
class Setting extends Model
{
    use HasFactory;

    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = ['value' => 'json'];

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

        self::updateOrCreate(compact('key'), compact('value'));
    }
}
