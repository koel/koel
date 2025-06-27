<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class Uuid extends TestableIdentifier
{
    public const REGEX = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

    protected static function newIdentifier(): string
    {
        return Str::uuid()->toString();
    }
}
