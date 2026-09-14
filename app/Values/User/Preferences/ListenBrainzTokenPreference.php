<?php

namespace App\Values\User\Preferences;

class ListenBrainzTokenPreference extends Preference
{
    /**
     * Spelled out instead of derived, as the brand's inner capital would snake_case into `listen_brainz_token`.
     */
    public function getKey(): string
    {
        return 'listenbrainz_token';
    }

    public function getProperty(): string
    {
        return 'listenBrainzToken';
    }

    public function isCustomizable(): bool
    {
        return false;
    }
}
