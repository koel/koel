<?php

namespace App\Models;

use App\Builders\UserBuilder;
use App\Casts\UserPreferencesCast;
use App\Enums\Acl\Role as RoleEnum;
use App\Models\Concerns\Users\HasUserAttributes;
use App\Models\Concerns\Users\HasUserRelationships;
use App\Models\Contracts\Permissionable;
use App\Observers\UserObserver;
use App\Values\User\UserPreferences;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property ?Carbon $invitation_accepted_at
 * @property ?Carbon $invited_at
 * @property ?User $invitedBy
 * @property ?string $invitation_token
 * @property Collection<array-key, Playlist> $collaboratedPlaylists
 * @property Collection<array-key, Playlist> $playlists
 * @property Collection<array-key, PlaylistFolder> $playlistFolders
 * @property Collection<array-key, Podcast> $podcasts
 * @property Collection<array-key, Theme> $themes
 * @property Organization $organization
 * @property PersonalAccessToken $currentAccessToken
 * @property UserPreferences $preferences
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
 * @property-read RoleEnum $role
 *
 * @method static UserFactory factory(...$parameters)
 */
#[ObservedBy(UserObserver::class)]
#[UseEloquentBuilder(UserBuilder::class)]
class User extends Authenticatable implements AuditableContract, Permissionable
{
    use Auditable;
    use HasApiTokens;
    use HasFactory;
    use HasRoles {
        scopeRole as scopeWhereRole;
    }
    use HasUserAttributes;
    use HasUserRelationships;
    use Notifiable;
    use Prunable;

    public const string FIRST_ADMIN_NAME = 'Koel';
    public const string FIRST_ADMIN_EMAIL = 'admin@koel.dev';
    public const string FIRST_ADMIN_PASSWORD = 'KoelIsCool';
    public const string DEMO_PASSWORD = 'demo';
    public const string DEMO_USER_DOMAIN = 'demo.koel.dev';

    protected $guarded = ['id', 'public_id'];
    protected $hidden = ['password', 'remember_token', 'created_at', 'updated_at', 'invitation_accepted_at'];
    protected $appends = ['avatar'];
    protected array $auditExclude = ['password', 'remember_token', 'invitation_token'];
    protected $with = ['roles', 'permissions'];

    protected function casts(): array
    {
        return [
            'preferences' => UserPreferencesCast::class,
        ];
    }

    // @mago-ignore lint:no-redundant-method-override
    public static function query(): UserBuilder
    {
        /** @var UserBuilder */
        return parent::query();
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** Delete all old and inactive demo users */
    public function prunable(): Builder
    {
        if (!config('koel.misc.demo')) {
            return static::query()->whereRaw('false');
        }

        return static::query()
            ->where('created_at', '<=', now()->subWeek())
            ->where('email', 'like', '%@' . self::DEMO_USER_DOMAIN)
            ->whereDoesntHave('interactions', static function (Builder $query): void {
                $query->where('last_played_at', '>=', now()->subDays(7));
            });
    }

    public function subscribedToPodcast(Podcast $podcast): bool
    {
        return $this->podcasts()->whereKey($podcast)->exists();
    }

    public static function getPermissionableIdentifier(): string
    {
        return 'public_id';
    }
}
