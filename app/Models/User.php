<?php

namespace App\Models;

use App\Services\LastfmService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property array  $preferences
 * @property int    $id
 * @property bool   $is_admin
 */
class User extends Authenticatable
{
    use Notifiable;

    /**
     * The preferences that we don't want to show to the client.
     *
     * @var array
     */
    private const HIDDEN_PREFERENCES = [LastfmService::SESSION_KEY_PREFERENCE_KEY];

    protected $guarded = ['id'];
    protected $casts = [
        'id' => 'int',
        'is_admin' => 'bool',
    ];
    protected $hidden = ['password', 'remember_token', 'created_at', 'updated_at'];

    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    /**
     * User preferences are stored as a serialized associative array.
     *
     * @param mixed[] $value
     */
    public function setPreferencesAttribute(array $value): void
    {
        $this->attributes['preferences'] = serialize($value);
    }

    /**
     * Unserialize the user preferences back to an array before returning.
     *
     * @return mixed[]
     */
    public function getPreferencesAttribute(?string $value): array
    {
        $preferences = unserialize($value) ?: [];

        // Hide sensitive data from returned preferences.
        foreach (self::HIDDEN_PREFERENCES as $key) {
            if (array_key_exists($key, $preferences)) {
                $preferences[$key] = 'hidden';
            }
        }

        return $preferences;
    }
}
