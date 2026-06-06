<?php

namespace App\Values\User\Preferences;

use Webmozart\Assert\Assert;

class RepeatModePreference extends Preference
{
    public function getDefaultValue(): string
    {
        return 'NO_REPEAT';
    }

    public function assert(): void
    {
        Assert::oneOf($this->value, ['NO_REPEAT', 'REPEAT_ALL', 'REPEAT_ONE']);
    }
}
