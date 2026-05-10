<?php

namespace App\Values\User\Preferences;

use Webmozart\Assert\Assert;

class ArtistsViewModePreference extends Preference
{
    public function getDefaultValue(): string
    {
        return 'thumbnails';
    }

    public function assert(): void
    {
        Assert::oneOf($this->value, ['list', 'thumbnails']);
    }
}
