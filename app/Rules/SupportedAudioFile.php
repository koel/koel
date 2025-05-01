<?php

namespace App\Rules;

use Closure;
use getID3;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class SupportedAudioFile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $passes = rescue(static function () use ($value) {
            Assert::oneOf(
                Arr::get((new getID3())->analyze($value->getRealPath()), 'fileformat'),
                config('koel.supported_formats')
            );

            return true;
        }) ?? false;

        if (!$passes) {
            $fail('Unsupported audio file');
        }
    }
}
