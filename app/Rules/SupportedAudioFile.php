<?php

namespace App\Rules;

use getID3;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Throwable;
use Webmozart\Assert\Assert;

class SupportedAudioFile implements Rule
{
    private const SUPPORTED_FORMATS = ['mp3', 'aac', 'ogg', 'flac', 'wav'];

    /** @param UploadedFile $value */
    public function passes($attribute, $value): bool
    {
        try {
            Assert::oneOf(
                Arr::get((new getID3())->analyze($value->getRealPath()), 'fileformat'),
                self::SUPPORTED_FORMATS
            );

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function message(): string
    {
        return 'Unsupported audio file';
    }
}
