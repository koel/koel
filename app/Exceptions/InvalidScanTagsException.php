<?php

namespace App\Exceptions;

use InvalidArgumentException;

class InvalidScanTagsException extends InvalidArgumentException
{
    public static function bothIgnoredAndOnly(): self
    {
        return new self('Use either --ignore or --only, not both.');
    }

    public static function noTagsInOnly(): self
    {
        return new self('Give at least one tag to --only.');
    }

    /** @param array<string> $tags */
    public static function unknownTags(array $tags): self
    {
        return new self('Unknown tag(s): ' . implode(', ', $tags));
    }
}
