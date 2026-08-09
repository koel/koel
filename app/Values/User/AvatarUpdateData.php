<?php

namespace App\Values\User;

/**
 * Wraps the new avatar of a user, distinguishing "the avatar is being changed to nothing"
 * (i.e., removed, falling back to the Gravatar) from "the avatar isn't part of this update at all,"
 * which is represented by the absence of this object altogether.
 */
final readonly class AvatarUpdateData
{
    private function __construct(
        public ?string $image,
    ) {}

    public static function make(?string $image = null): self
    {
        return new self($image);
    }
}
