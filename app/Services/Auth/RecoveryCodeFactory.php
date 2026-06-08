<?php

namespace App\Services\Auth;

use Illuminate\Support\Str;
use Webmozart\Assert\Assert;

class RecoveryCodeFactory
{
    private static function generateCode(): string
    {
        return Str::of(Str::random(32))->upper()->split(4)->join(' ');
    }

    /** @return list<string> */
    public function generateCodes(int $count): array
    {
        Assert::greaterThan($count, 0);

        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = self::generateCode();
        }

        return $codes;
    }
}
