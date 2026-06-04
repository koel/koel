<?php

namespace App\Values\Podcast;

use App\Models\Song as Episode;
use App\Services\Network\SafeHttp;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

final class EpisodePlayable implements Arrayable, Jsonable
{
    private function __construct(
        public readonly string $path,
        public readonly string $checksum,
    ) {}

    public static function make(string $path, string $sum): self
    {
        return new self($path, $sum);
    }

    public function valid(): bool
    {
        return File::isReadable($this->path) && $this->checksum === File::hash($this->path);
    }

    public static function getForEpisode(Episode $episode, SafeHttp $safeHttp): self
    {
        /** @var self|null $cached */
        $cached = Cache::get("episode-playable.{$episode->id}");

        return $cached?->valid() ? $cached : self::createForEpisode($episode, $safeHttp);
    }

    private static function createForEpisode(Episode $episode, SafeHttp $safeHttp): self
    {
        $file = artifact_path("episodes/{$episode->id}.mp3");

        if (!File::exists($file)) {
            $safeHttp->download((string) $episode->path, $file)->throw();
        }

        $playable = new self($file, File::hash($file));
        Cache::forever("episode-playable.{$episode->id}", $playable);

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
