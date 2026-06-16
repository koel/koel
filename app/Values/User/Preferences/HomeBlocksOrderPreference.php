<?php

namespace App\Values\User\Preferences;

use Webmozart\Assert\Assert;

class HomeBlocksOrderPreference extends Preference
{
    /** @return list<string> */
    public function getDefaultValue(): array
    {
        return [];
    }

    public function assert(): void
    {
        Assert::isArray($this->value);
        Assert::allString($this->value);
    }

    /** @return list<string> */
    protected function cast(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }
}
