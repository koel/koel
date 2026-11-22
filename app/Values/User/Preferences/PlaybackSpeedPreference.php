<?php

namespace App\Values\User\Preferences;

use Webmozart\Assert\Assert;

abstract class PlaybackSpeedPreference extends Preference
{
    public function getDefaultValue(): float
    {
        return 1.0;
    }

    public function assert(): void
    {
        Assert::oneOf($this->value, [0.75, 1.0, 1.25, 1.5, 1.75, 2.0]);
    }

    protected function cast(mixed $value): float
    {
        return (float) $value;
    }
}
