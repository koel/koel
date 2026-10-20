<?php

namespace App\Values;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final readonly class Downloadable
{
    private bool $redirectable;

    private function __construct(
        public string $path,
        public ?string $name,
        public bool $isTemporaryCopy,
    ) {
        $this->redirectable = Str::startsWith($path, ['http://', 'https://']);
    }

    public static function make(string $path, ?string $name = null, bool $isTemporaryCopy = false): self
    {
        return new self($path, $name, $isTemporaryCopy);
    }

    public function toResponse(): Response
    {
        if ($this->redirectable) {
            return response()->redirectTo($this->path);
        }

        return response()->download($this->path, $this->name)->deleteFileAfterSend($this->isTemporaryCopy);
    }
}
