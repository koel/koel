<?php

namespace App\Values\Podcast;

use App\Models\Song as Episode;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

final class EpisodePlayable implements Arrayable, Jsonable
{
    private function __construct(public readonly string $path, public readonly string $checksum)
    {
    }

    public static function make(string $path, string $sum): self
    {
        return new self($path, $sum);
    }

    public function valid(): bool
    {
        return File::isReadable($this->path) && $this->checksum === md5_file($this->path);
    }

    public static function getForEpisode(Episode $episode): ?self
    {
        /** @var self|null $cached */
        $cached = Cache::get("episode-playable.$episode->id");

        return $cached?->valid() ? $cached : self::createForEpisode($episode);
    }

    private static function createForEpisode(Episode $episode): self
    {
        $dir = sys_get_temp_dir() . '/koel-episodes';
        $file = sprintf('%s/%s.mp3', $dir, $episode->id);

        if (!File::exists($file)) {
            File::ensureDirectoryExists($dir);
            Http::sink($file)->get($episode->path)->throw();
        }

        $playable = new self($file, md5_file($file));
        Cache::forever("episode-playable.$episode->id", $playable);

        return $playable;
    }

    /** @inheritDoc */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'checksum' => $this->checksum,
        ];
    }

    /** @inheritDoc */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
