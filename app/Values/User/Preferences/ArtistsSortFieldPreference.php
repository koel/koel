<?php

namespace App\Values\User\Preferences;

use Webmozart\Assert\Assert;

class ArtistsSortFieldPreference extends Preference
{
    public function getDefaultValue(): string
    {
        return 'name';
    }

    public function assert(): void
    {
        Assert::oneOf($this->value, ['name', 'created_at']);
    }
}
