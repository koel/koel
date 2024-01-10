<?php

namespace App\Models;

use App\Casts\EncryptedValueCast;
use App\Casts\LicenseInstanceCast;
use App\Casts\LicenseMetaCast;
use App\Values\LicenseInstance;
use App\Values\LicenseMeta;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property-read string $short_key
 * @property string $key
 * @property LicenseInstance $instance An activation of the license.
 * @see https://docs.lemonsqueezy.com/api/license-key-instances
 * @property LicenseMeta $meta
 * @property-read Carbon $activated_at
 */
class License extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'key' => EncryptedValueCast::class,
        'instance' => LicenseInstanceCast::class,
        'meta' => LicenseMetaCast::class,
        'expires_at' => 'datetime',
    ];

    protected function shortKey(): Attribute
    {
        return Attribute::get(fn (): string => '****-' . Str::afterLast($this->key, '-'));
    }

    protected function activatedAt(): Attribute
    {
        return Attribute::get(fn () => $this->instance->createdAt);
    }
}
