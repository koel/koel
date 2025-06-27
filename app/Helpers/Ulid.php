<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class Ulid extends TestableIdentifier
{
    protected static function newIdentifier(): string
    {
        return Str::lower(Str::ulid());
    }
}
