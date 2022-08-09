<?php

namespace App\Models;

use App\Casts\UserPreferencesCast;
use App\Values\UserPreferences;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property UserPreferences $preferences
 * @property int $id
 * @property bool $is_admin
 * @property ?string $lastfm_session_key
 * @property string $name
 * @property string $email
 * @property string $password
 * @property-read string $avatar
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $guarded = ['id'];
    protected $hidden = ['password', 'remember_token', 'created_at', 'updated_at'];
    protected $appends = ['avatar'];

    protected $casts = [
        'is_admin' => 'bool',
        'preferences' => UserPreferencesCast::class,
    ];

    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    protected function avatar(): Attribute
    {
        return Attribute::get(
            fn () => sprintf('https://www.gravatar.com/avatar/%s?s=192&d=robohash', md5($this->email))
        );
    }

    /**
     * Get the user's Last.fm session key.
     */
    protected function lastfmSessionKey(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->preferences->lastFmSessionKey);
    }

    /**
     * Determine if the user is connected to Last.fm.
     */
    public function connectedToLastfm(): bool
    {
        return (bool) $this->lastfm_session_key;
    }
}
