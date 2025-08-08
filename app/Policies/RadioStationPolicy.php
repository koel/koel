<?php

namespace App\Policies;

use App\Models\RadioStation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RadioStationPolicy
{
    public function access(User $user, RadioStation $station): Response
    {
        if ($station->user_id === $user->id) {
            return Response::allow();
        }

        if ($station->is_public) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function edit(User $user, RadioStation $station): Response
    {
        // For radio stations, if the user is an admin of the organization, they can update any station.
        // This is regardless of the license type (unlike artists or albums).
        if ($user->is_admin && $user->organization_id === $station->user->organization_id) {
            return Response::allow();
        }

        if ($station->user_id === $user->id) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function update(User $user, RadioStation $station): Response
    {
        // The update policy is the same as edit for radio stations.
        return $this->edit($user, $station);
    }

    public function delete(User $user, RadioStation $station): Response
    {
        // The delete policy is the same as edit for radio stations.
        return $this->update($user, $station);
    }
}
