<?php

namespace App\Values;

final readonly class ParsedLyrics
{
    /** @param list<array{start?: int, value: string}> $lines */
    private function __construct(
        public bool $synced,
        public int $offset,
        public array $lines,
    ) {}

    /** @param list<array{start?: int, value: string}> $lines */
    public static function make(bool $synced, int $offset, array $lines): self
    {
        return new self($synced, $offset, $lines);
    }

    public static function fromRawLyrics(string $raw): self
    {
        $raw = trim($raw);

        if ($raw === '') {
            return new self(synced: false, offset: 0, lines: []);
        }

        $offset = 0;
        $timedLines = [];
        $plainLines = [];

        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            if (preg_match('/^\s*\[offset:\s*([+-]?\d+)\s*]\s*$/i', $line, $matches)) {
                $offset = (int) $matches[1];
                continue;
            }

            if (preg_match('/^\s*((?:\[\d{1,2}:\d{2}(?:[.:]\d{1,3})?]\s*)+)(.*)$/', $line, $matches)) {
                $text = trim($matches[2]);
                preg_match_all('/\[(\d{1,2}):(\d{2})(?:[.:](\d{1,3}))?]/', $matches[1], $tags, PREG_SET_ORDER);

                foreach ($tags as $tag) {
                    $start = ((int) $tag[1] * 60_000) + ((int) $tag[2] * 1000);
                    $fraction = $tag[3] ?? '';

                    if ($fraction !== '') {
                        $start += (int) round((float) "0.$fraction" * 1000);
                    }

                    $timedLines[] = ['start' => $start, 'value' => $text];
                }

                continue;
            }

            $plainLines[] = ['value' => $line];
        }

        if ($timedLines) {
            usort($timedLines, static fn (array $a, array $b) => $a['start'] <=> $b['start']);

            return new self(synced: true, offset: $offset, lines: $timedLines);
        }

        return new self(synced: false, offset: $offset, lines: $plainLines);
    }
}
