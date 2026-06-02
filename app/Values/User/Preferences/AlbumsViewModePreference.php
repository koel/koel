<?php

namespace App\Values\User\Preferences;

use Webmozart\Assert\Assert;

class AlbumsViewModePreference extends Preference
{
    public function getDefaultValue(): string
    {
        return 'grid';
    }

    public function assert(): void
    {
        Assert::oneOf($this->value, ['grid', 'table']);
    }

    protected function cast(mixed $value): mixed
    {
        return $value === 'thumbnails' ? 'grid' : $value;
    }
}
