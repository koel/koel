<?php

namespace App\Models;

use App\Casts\UserPreferencesCast;
use App\Exceptions\UserAlreadySubscribedToPodcastException;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property ?Carbon $invitation_accepted_at
 * @property ?Carbon $invited_at
 * @property ?User $invitedBy
 * @property ?string $invitation_token
 * @property Collection<array-key, Playlist> $collaboratedPlaylists
 * @property Collection<array-key, Playlist> $playlists
 * @property Collection<array-key, PlaylistFolder> $playlist_folders
 * @property Collection<array-key, Podcast> $podcasts
 * @property Organization $organization
 * @property PersonalAccessToken $currentAccessToken
 * @property UserPreferences $preferences
 * @property bool $is_admin
 * @property int $id
 * @property string $email
 * @property string $name
 * @property string $organization_id
 * @property string $password
 * @property string $public_id
 * @property-read ?string $sso_id
 * @property-read ?string $sso_provider
 * @property-read bool $connected_to_lastfm Whether the user is connected to Last.fm
 * @property-read bool $has_custom_avatar
 * @property-read bool $is_prospect
 * @property-read bool $is_sso
 * @property-read string $avatar
 */
class User extends Authenticatable implements AuditableContract
{
    use Auditable;
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    private const FIRST_ADMIN_NAME = 'Koel';
    public const FIRST_ADMIN_EMAIL = 'admin@koel.dev';
    public const FIRST_ADMIN_PASSWORD = 'KoelIsCool';

    protected $guarded = ['id', 'public_id'];
    protected $hidden = ['password', 'remember_token', 'created_at', 'updated_at', 'invitation_accepted_at'];
    protected $appends = ['avatar'];
    protected array $auditExclude = ['password', 'remember_token', 'invitation_token'];

    protected $casts = [
        'is_admin' => 'bool',
        'preferences' => UserPreferencesCast::class,
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $user): void {
            $user->public_id ??= Str::uuid()->toString();
        });
    }

    /**
     * The first admin user in the system.
     * This user is created automatically if it does not exist (e.g., during installation or unit tests).
     */
    public static function firstAdmin(): static
    {
        $defaultOrganization = Organization::default();

        return static::query()
            ->where([
                'is_admin' => true,
                'organization_id' => $defaultOrganization->id,
            ])
            ->oldest()
            ->firstOr(static function () use ($defaultOrganization): User {
                return static::query()->create([
                    'is_admin' => true,
                    'email' => self::FIRST_ADMIN_EMAIL,
                    'name' => self::FIRST_ADMIN_NAME,
                    'password' => Hash::make(self::FIRST_ADMIN_PASSWORD),
                    'organization_id' => $defaultOrganization->id,
                ]);
            });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'invited_by_id');
    }

    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class)
            ->withPivot('role', 'position')
            ->withTimestamps();
    }

    public function ownedPlaylists(): BelongsToMany
    {
        return $this->playlists()->wherePivot('role', 'owner');
    }

    public function collaboratedPlaylists(): BelongsToMany
    {
        return $this->playlists()->wherePivot('role', 'collaborator');
    }

    public function playlist_folders(): HasMany // @phpcs:ignore
    {
        return $this->hasMany(PlaylistFolder::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public function podcasts(): BelongsToMany
    {
        return $this->belongsToMany(Podcast::class)
            ->using(PodcastUserPivot::class)
            ->withTimestamps();
    }

    public function subscribedToPodcast(Podcast $podcast): bool
    {
        return $this->podcasts()->whereKey($podcast)->exists();
    }

    public function subscribeToPodcast(Podcast $podcast): void
    {
        throw_if(
            $this->subscribedToPodcast($podcast),
            UserAlreadySubscribedToPodcastException::create($this, $podcast)
        );

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
        return Attribute::get(fn (): bool => (bool)$this->invitation_token);
    }

    protected function isSso(): Attribute
    {
        return Attribute::get(fn (): bool => License::isPlus() && $this->sso_provider)->shouldCache();
    }

    protected function connectedToLastfm(): Attribute
    {
        return Attribute::get(fn (): bool => (bool)$this->preferences->lastFmSessionKey)->shouldCache();
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
