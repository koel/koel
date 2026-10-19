<?php

namespace Tests\Fakes;

/**
 * A stream wrapper that hands out at most a few bytes per read, the way a slow network stream does.
 */
class TrickleStream
{
    public const string PROTOCOL = 'trickle';

    public static string $content = '';

    public static int $maxBytesPerRead = 3;

    /** @var resource|null */
    public $context;

    private int $position = 0;

    public static function open(string $content): mixed
    {
        if (!in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
            stream_wrapper_register(self::PROTOCOL, self::class);
        }

        self::$content = $content;

        return fopen(self::PROTOCOL . '://stream', 'r');
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        return true;
    }

    public function stream_read(int $count): string
    {
        $chunk = substr(self::$content, $this->position, min($count, self::$maxBytesPerRead));
        $this->position += strlen($chunk);

        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen(self::$content);
    }
}
