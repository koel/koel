<?php

namespace App\Values\Podcast;

use App\Exceptions\UnsafeUrlException;
use App\Helpers\Network;
use App\Models\Song as Episode;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

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

    public static function getForEpisode(Episode $episode): self
    {
        /** @var self|null $cached */
        $cached = Cache::get("episode-playable.{$episode->id}");

        return $cached?->valid() ? $cached : self::createForEpisode($episode);
    }

    private static function createForEpisode(Episode $episode): self
    {
        $file = artifact_path("episodes/{$episode->id}.mp3");

        if (!File::exists($file)) {
            $network = app(Network::class);
            $url = (string) $episode->path;

            if (!$network->isSafeUrl($url)) {
                throw UnsafeUrlException::forUrl($url);
            }

            Http::sink($file)
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 5,
                        'on_redirect' => static function (
                            RequestInterface $request,
                            ResponseInterface $response,
                            UriInterface $uri,
                        ) use ($network): void {
                            if (!$network->isSafeUrl((string) $uri)) {
                                throw UnsafeUrlException::forUrl((string) $uri);
                            }
                        },
                    ],
                ])
                ->get($url)
                ->throw();
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
