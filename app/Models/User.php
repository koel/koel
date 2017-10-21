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
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The preferences that we don't want to show to the client.
     *
     * @var array
     */
    protected $hiddenPreferences = ['lastfm_session_key'];

    /**
     * The attributes that are protected from mass assign.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $casts = [
        'id' => 'int',
        'is_admin' => 'bool',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token', 'created_at', 'updated_at'];

    /**
     * A user can have many playlists.
     *
     * @return HasMany
     */
    public function playlists()
    {
        return $this->hasMany(Playlist::class);
    }

    /**
     * A user can make multiple interactions.
     *
     * @return HasMany
     */
    public function interactions()
    {
        return $this->hasMany(Interaction::class);
    }

    /**
     * Get a preference item of the current user.
     *
     * @param string $key
     *
     * @return string|null
     */
    public function getPreference($key)
    {
        // We can't use $this->preferences directly, since the data has been tampered
        // by getPreferencesAttribute().
        return array_get((array) unserialize($this->attributes['preferences']), $key);
    }

    /**
     * Save a user preference.
     *
     * @param string $key
     * @param string $val
     */
    public function savePreference($key, $val)
    {
        $preferences = $this->preferences;
        $preferences[$key] = $val;
        $this->preferences = $preferences;

        $this->save();
    }

    /**
     * An alias to savePreference().
     *
     * @see $this::savePreference
     *
     * @param $key
     * @param $val
     */
    public function setPreference($key, $val)
    {
        return $this->savePreference($key, $val);
    }

    /**
     * Delete a preference.
     *
     * @param string $key
     */
    public function deletePreference($key)
    {
        $preferences = $this->preferences;
        array_forget($preferences, $key);

        $this->update(compact('preferences'));
    }

    /**
     * Determine if the user is connected to Last.fm.
     *
     * @return bool
     */
    public function connectedToLastfm()
    {
        return (bool) $this->lastfm_session_key;
    }

    /**
     * Get the user's Last.fm session key.
     *
     * @return string|null The key if found, or null if user isn't connected to Last.fm
     */
    public function getLastfmSessionKeyAttribute()
    {
        return $this->getPreference('lastfm_session_key');
    }

    /**
     * User preferences are stored as a serialized associative array.
     *
     * @param array $value
     */
    public function setPreferencesAttribute($value)
    {
        $this->attributes['preferences'] = serialize($value);
    }

    /**
     * Unserialize the user preferences back to an array before returning.
     *
     * @param string $value
     *
     * @return array
     */
    public function getPreferencesAttribute($value)
    {
        $preferences = unserialize($value) ?: [];

        // Hide sensitive data from returned preferences.
        foreach ($this->hiddenPreferences as $key) {
            if (array_key_exists($key, $preferences)) {
                $preferences[$key] = 'hidden';
            }
        }

        return $preferences;
    }
}
