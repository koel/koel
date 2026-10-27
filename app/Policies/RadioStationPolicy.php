<?php

namespace App\Policies;

use App\Enums\Acl\Permission;
use App\Enums\Acl\Role;
use App\Models\RadioStation;
use App\Models\User;

class RadioStationPolicy
{
    public function access(User $user, RadioStation $station): bool
    {
        if ($station->user_id === $user->id) {
            return true;
        }

        return $station->is_public && $station->user->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->role !== Role::GUEST;
    }

    public function edit(User $user, RadioStation $station): bool
    {
        // For radio stations, if the user has the permission, they can update any station.
        // This is regardless of the license type (unlike artists or albums).
        if (
            $user->hasPermissionTo(Permission::MANAGE_RADIO_STATIONS)
            && $user->organization_id === $station->user->organization_id
        ) {
            return true;
        }

        return $station->user_id === $user->id;
    }

    public function update(User $user, RadioStation $station): bool
    {
        // The update policy is the same as edit for radio stations.
        return $this->edit($user, $station);
    }

    public function delete(User $user, RadioStation $station): bool
    {
        // The delete policy is the same as edit for radio stations.
        return $this->update($user, $station);
    }
}
