<?php

namespace App\Models;

use App\Casts\UserPreferencesCast;
use App\Facades\License;
use App\Models\Podcast\Podcast;
use App\Models\Podcast\PodcastUserPivot;
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
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @property UserPreferences $preferences
 * @property int $id
 * @property bool $is_admin
 * @property string $name
 * @property string $email
 * @property string $password
 * @property-read bool $has_custom_avatar
 * @property-read string $avatar
 * @property Collection<array-key, Playlist> $playlists
 * @property Collection<array-key, PlaylistFolder> $playlist_folders
 * @property PersonalAccessToken $currentAccessToken
 * @property ?Carbon $invitation_accepted_at
 * @property ?User $invitedBy
 * @property ?string $invitation_token
 * @property ?Carbon $invited_at
 * @property-read bool $is_prospect
 * @property Collection<array-key, Playlist> $collaboratedPlaylists
 * @property ?string $sso_provider
 * @property ?string $sso_id
 * @property bool $is_sso
 * @property Collection<array-key, Podcast> $podcast
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

    protected function avatar(): Attribute
    {
        return Attribute::get(function (): string {
            $avatar = Arr::get($this->attributes, 'avatar');

            if (Str::startsWith($avatar, ['http://', 'https://'])) {
                return $avatar;
            }

            return $avatar ? user_avatar_url($avatar) : gravatar($this->email);
        });
    }

    protected function hasCustomAvatar(): Attribute
    {
        return Attribute::get(fn (): bool => (bool) $this->attributes['avatar']);
    }

    protected function isProspect(): Attribute
    {
        return Attribute::get(fn (): bool => (bool) $this->invitation_token);
    }

    protected function isSso(): Attribute
    {
        return Attribute::get(fn (): bool => License::isPlus() && $this->sso_provider);
    }

    /**
     * Determine if the user is connected to Last.fm.
     */
    public function connectedToLastfm(): bool
    {
        return (bool) $this->preferences->lastFmSessionKey;
    }
}
