<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;

class User extends Model implements
AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;

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

    public function playlists()
    {
        return $this->hasMany(Playlist::class);
    }

    public function interactions()
    {
        return $this->hasMany(Interaction::class);
    }

    /**
     * Get a preference item of the current user.
     * 
     * @param  string      $key
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

        // Hide the user's secrets away!
        foreach ($this->hiddenPreferences as $key) {
            if (isset($preferences[$key])) {
                $preferences[$key] = 'hidden';
            }
        }

        return $preferences;
    }
}
