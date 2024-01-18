<?php

namespace App\Models;

use App\Casts\UserPreferencesCast;
use App\Values\UserPreferences;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @property UserPreferences $preferences
 * @property int $id
 * @property bool $is_admin
 * @property ?string $lastfm_session_key
 * @property string $name
 * @property string $email
 * @property string $password
 * @property-read string $avatar
 * @property Collection|array<array-key, Playlist> $playlists
 * @property Collection|array<array-key, PlaylistFolder> $playlist_folders
 * @property PersonalAccessToken $currentAccessToken
 * @property ?Carbon $invitation_accepted_at
 * @property ?User $invitedBy
 * @property ?string $invitation_token
 * @property ?Carbon $invited_at
 * @property-read bool $is_prospect
 * @property Collection|array<array-key, Playlist> $collaboratedPlaylists
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $guarded = ['id'];
    protected $hidden = ['password', 'remember_token', 'created_at', 'updated_at', 'invitation_accepted_at'];
    protected $appends = ['avatar'];

    protected $casts = [
        'is_admin' => 'bool',
        'preferences' => UserPreferencesCast::class,
    ];

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }

    public function collaboratedPlaylists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class, 'playlist_collaborators')->withTimestamps();
    }

    public function playlist_folders(): HasMany // @phpcs:ignore
    {
        return $this->hasMany(PlaylistFolder::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    protected function avatar(): Attribute
    {
        return Attribute::get(fn (): string => gravatar($this->email));
    }

    /**
     * Get the user's Last.fm session key.
     */
    protected function lastfmSessionKey(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->preferences->lastFmSessionKey);
    }

    protected function isProspect(): Attribute
    {
        return Attribute::get(fn (): bool => (bool) $this->invitation_token);
    }

    /**
     * Determine if the user is connected to Last.fm.
     */
    public function connectedToLastfm(): bool
    {
        return (bool) $this->lastfm_session_key;
    }
}
