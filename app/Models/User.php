<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property array  preferences
 * @property int    id
 * @property bool   is_admin
 * @property string lastfm_session_key
 */
class User extends Authenticatable
{
    use Notifiable;

    /**
     * The preferences that we don't want to show to the client.
     *
     * @var array
     */
    private const HIDDEN_PREFERENCES = ['lastfm_session_key'];

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
     * @return mixed|null
     */
    public function getPreference(string $key)
    {
        // We can't use $this->preferences directly, since the data has been tampered
        // by getPreferencesAttribute().
        return array_get((array) unserialize($this->attributes['preferences']), $key);
    }

    /**
     * @param mixed $val
     */
    public function savePreference(string $key, $val): void
    {
        $preferences = $this->preferences;
        $preferences[$key] = $val;
        $this->preferences = $preferences;

        $this->save();
    }

    /**
     * An alias to savePreference().
     *
     * @param mixed $val
     *
     * @see self::savePreference
     */
    public function setPreference(string $key, $val): void
    {
        $this->savePreference($key, $val);
    }

    public function deletePreference(string $key): void
    {
        $preferences = $this->preferences;
        array_forget($preferences, $key);

        $this->update(compact('preferences'));
    }

    /**
     * Determine if the user is connected to Last.fm.
     */
    public function connectedToLastfm(): bool
    {
        return (bool) $this->lastfm_session_key;
    }

    /**
     * Get the user's Last.fm session key.
     *
     * @return string|null The key if found, or null if user isn't connected to Last.fm
     */
    public function getLastfmSessionKeyAttribute(): ?string
    {
        return $this->getPreference('lastfm_session_key');
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
    public function getPreferencesAttribute(string $value): array
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
