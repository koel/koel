<?php

namespace App\Services;

use App\Models\User;

class UserPreferenceService
{
    /**
     * @return mixed
     */
    public function get(User $user, string $key)
    {
        return array_get((array) unserialize($user->getOriginal('preferences')), $key);
    }

    /**
     * @param mixed $val
     */
    public function set(User $user, string $key, $val): void
    {
        $preferences = $user->preferences;
        $preferences[$key] = $val;
        $user->preferences = $preferences;

        $user->save();
    }

    public function delete(User $user, string $key): void
    {
        $preferences = $user->preferences;
        array_forget($preferences, $key);

        $user->update(compact('preferences'));
    }
}
