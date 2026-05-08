<?php

namespace App\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Env;
use LogicException;

class DotenvEditor
{
    private ?string $backup = null;

    public function __construct(
        private readonly string $path,
        private readonly Filesystem $filesystem,
    ) {}

    public function setKey(string $key, mixed $value): self
    {
        Env::writeVariable($key, $value, $this->path, overwrite: true);

        return $this;
    }

    /** @param array<string, mixed> $variables */
    public function setKeys(array $variables): self
    {
        Env::writeVariables($variables, $this->path, overwrite: true);

        return $this;
    }

    public function backup(): self
    {
        $this->backup = $this->filesystem->get($this->path);

        return $this;
    }

    public function restore(): void
    {
        if ($this->backup === null) {
            throw new LogicException('No backup captured. Call backup() before restore().');
        }

        $this->filesystem->put($this->path, $this->backup);
        $this->backup = null;
    }
}
