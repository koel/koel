<?php

namespace App\Values\User\Preferences;

use Webmozart\Assert\Assert;

class ActiveExtraPanelTabPreference extends Preference
{
    public function assert(): void
    {
        Assert::oneOf($this->value, [null, 'Lyrics', 'Artist', 'Album', 'YouTube']);
    }
}
