<?php

namespace App\Values\User\Preferences;

use Webmozart\Assert\Assert;

class AlbumsSortFieldPreference extends Preference
{
    public function getDefaultValue(): string
    {
        return 'name';
    }

    public function assert(): void
    {
        Assert::oneOf($this->value, ['name', 'artist_name', 'year', 'created_at']);
    }
}
