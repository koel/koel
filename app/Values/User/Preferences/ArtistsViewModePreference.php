<?php

namespace App\Values\User\Preferences;

use Webmozart\Assert\Assert;

class ArtistsViewModePreference extends Preference
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
        return match ($value) {
            'thumbnails' => 'grid',
            'list' => 'table',
            default => $value,
        };
    }
}
