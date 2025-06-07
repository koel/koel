<?php

namespace App\Values;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class Downloadable
{
    private bool $redirectable;

    private function __construct(public readonly string $path)
    {
        $this->redirectable = Str::startsWith($path, ['http://', 'https://']);
    }

    public static function make(string $path): self
    {
        return new self($path);
    }

    public function toResponse(): Response
    {
        return $this->redirectable
            ? response()->redirectTo($this->path)
            : response()->download($this->path);
    }
}
