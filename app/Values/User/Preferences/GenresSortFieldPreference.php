<?php

namespace App\Values\User\Preferences;

use Webmozart\Assert\Assert;

class GenresSortFieldPreference extends Preference
{
    public function getDefaultValue(): string
    {
        return 'name';
    }

    public function assert(): void
    {
        Assert::oneOf($this->value, ['name', 'song_count']);
    }
}
