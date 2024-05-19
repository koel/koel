<?php

namespace App\Rules;

use Closure;
use getID3;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class SupportedAudioFile implements ValidationRule
{
    private const SUPPORTED_FORMATS = ['mp3', 'aac', 'ogg', 'flac', 'wav'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $passes = attempt(static function () use ($value) {
            Assert::oneOf(
                Arr::get((new getID3())->analyze($value->getRealPath()), 'fileformat'),
                self::SUPPORTED_FORMATS
            );

            return true;
        }, false) ?? false;

        if (!$passes) {
            $fail('Unsupported audio file');
        }
    }
}
