<?php

namespace App\Values\User\Preferences;

use Webmozart\Assert\Assert;

class CrossfadeDurationPreference extends Preference
{
    public function getDefaultValue(): int
    {
        return 0;
    }

    public function assert(): void
    {
        Assert::range($this->value, 0, 15);
    }

    protected function cast(mixed $value): int
    {
        return (int) $value;
    }
}
