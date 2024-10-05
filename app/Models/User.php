<?php

namespace App\Models;

use App\Casts\UserPreferencesCast;
use App\Exceptions\UserAlreadySubscribedToPodcast;
use App\Facades\License;
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
use Illuminate\Support\Arr;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @property ?Carbon $invitation_accepted_at
 * @property ?Carbon $invited_at
 * @property ?User $invitedBy
 * @property ?string $invitation_token
 * @property Collection<array-key, Playlist> $collaboratedPlaylists
 * @property Collection<array-key, Playlist> $playlists
 * @property Collection<array-key, PlaylistFolder> $playlist_folders
 * @property Collection<array-key, Podcast> $podcasts
 * @property PersonalAccessToken $currentAccessToken
 * @property UserPreferences $preferences
 * @property bool $is_admin
 * @property int $id
 * @property string $email
 * @property string $name
 * @property string $password
 * @property-read ?string $sso_id
 * @property-read ?string $sso_provider
 * @property-read bool $connected_to_lastfm Whether the user is connected to Last.fm
 * @property-read bool $has_custom_avatar
 * @property-read bool $is_prospect
 * @property-read bool $is_sso
 * @property-read string $avatar
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

    public function podcasts(): BelongsToMany
    {
        return $this->belongsToMany(Podcast::class)
            ->using(PodcastUserPivot::class)
            ->withTimestamps();
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

    public function subscribedToPodcast(Podcast $podcast): bool
    {
        return $this->podcasts()->where('podcast_id', $podcast->id)->exists();
    }

    public function subscribeToPodcast(Podcast $podcast): void
    {
        throw_if($this->subscribedToPodcast($podcast), UserAlreadySubscribedToPodcast::make($this, $podcast));

        $this->podcasts()->attach($podcast);
    }

    public function unsubscribeFromPodcast(Podcast $podcast): void
    {
        $this->podcasts()->detach($podcast);
    }

    protected function avatar(): Attribute
    {
        return Attribute::get(fn (): string => avatar_or_gravatar(Arr::get($this->attributes, 'avatar'), $this->email))
            ->shouldCache();
    }

    protected function hasCustomAvatar(): Attribute
    {
        return Attribute::get(fn (): bool => (bool) Arr::get($this->attributes, 'avatar'))
            ->shouldCache();
    }

    protected function isProspect(): Attribute
    {
        return Attribute::get(fn (): bool => (bool) $this->invitation_token);
    }

    protected function isSso(): Attribute
    {
        return Attribute::get(fn (): bool => License::isPlus() && $this->sso_provider)->shouldCache();
    }

    protected function connectedToLastfm(): Attribute
    {
        return Attribute::get(fn (): bool => (bool) $this->preferences->lastFmSessionKey)->shouldCache();
    }
}
