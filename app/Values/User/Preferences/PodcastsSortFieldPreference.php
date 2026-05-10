<?php

namespace App\Values\User\Preferences;

use Webmozart\Assert\Assert;

class PodcastsSortFieldPreference extends Preference
{
    public function getDefaultValue(): string
    {
        return 'title';
    }

    public function assert(): void
    {
        Assert::oneOf($this->value, ['title', 'last_played_at', 'subscribed_at', 'author']);
    }
}
