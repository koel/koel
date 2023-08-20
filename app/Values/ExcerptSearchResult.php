<?php

namespace App\DTO;

use Illuminate\Support\Collection;

final class ExcerptSearchResult
{
    private function __construct(public Collection $songs, public Collection $artists, public Collection $albums)
    {
    }

    public static function make(Collection $songs, Collection $artists, Collection $albums): self
    {
        return new self($songs, $artists, $albums);
    }
}
