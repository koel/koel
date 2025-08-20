<?php

namespace App\Builders;

use App\Builders\Concerns\CanScopeByUser;
use App\Models\User;
use LogicException;

class RadioStationBuilder extends FavoriteableBuilder
{
    use CanScopeByUser;

    private function accessible(): self
    {
        throw_unless($this->user, new LogicException('User must be set to query accessible radio stations.'));

        if (!$this->user->preferences->includePublicMedia) {
            // If the user does not want to include public media, we only return stations created by them.
            return $this->whereBelongsTo($this->user);
        }

        // otherwise, we return stations that are created by the user in the same organization.
        return $this->join('users', 'users.id', '=', 'radio_stations.user_id')
            ->join('organizations', 'organizations.id', '=', 'users.organization_id')
            ->where(function (self $builder): void {
                $builder->where('radio_stations.user_id', $this->user->id)
                    ->orWhere(function (self $query): void {
                        $query->where('radio_stations.is_public', true)
                            ->where('organizations.id', $this->user->organization_id);
                    });
            });
    }

    public function withUserContext(
        User $user,
        bool $includeFavoriteStatus = true,
        bool $favoritesOnly = false,
    ): self {
        $this->user = $user;

        return $this->accessible()
            ->when($includeFavoriteStatus, static fn (self $query) => $query->withFavoriteStatus($favoritesOnly));
    }
}
