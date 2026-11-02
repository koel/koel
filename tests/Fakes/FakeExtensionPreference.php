<?php

namespace Tests\Fakes;

use App\Values\User\Preferences\BooleanPreference;

class FakeExtensionPreference extends BooleanPreference
{
    public function getDefaultValue(): true
    {
        return true;
    }
}
