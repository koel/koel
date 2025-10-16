<?php

namespace App\Values\Theme;

use Illuminate\Contracts\Support\Arrayable;

final class ThemeProperties implements Arrayable
{
    private function __construct(
        public readonly string $fgColor,
        public readonly string $bgColor,
        public readonly string $bgImage,
        public readonly string $highlightColor,
        public readonly string $fontFamily,
        public readonly float $fontSize,
    ) {
    }

    public static function make(
        string $fgColor,
        string $bgColor,
        string $bgImage,
        string $highlightColor,
        string $fontFamily,
        float $fontSize,
    ): self {
        return new self(
            fgColor: $fgColor,
            bgColor: $bgColor,
            bgImage: $bgImage,
            highlightColor: $highlightColor,
            fontFamily: $fontFamily,
            fontSize: $fontSize,
        );
    }

    public static function empty(): self
    {
        return new self(
            fgColor: '',
            bgColor: '',
            bgImage: '',
            highlightColor: '',
            fontFamily: '',
            fontSize: 13.0,
        );
    }

    public static function unserialize(object $json): self
    {
        return new self(
            fgColor: object_get($json, '--color-fg', ''),
            bgColor: object_get($json, '--color-bg', ''),
            bgImage: object_get($json, '--bg-image', ''),
            highlightColor: object_get($json, '--color-highlight', ''),
            fontFamily: object_get($json, '--font-family', ''),
            fontSize: object_get($json, '--font-size', 13.0),
        );
    }

    public function serialize(): string
    {
        $arr = $this->toArray();
        $arr['--bg-image'] = $this->bgImage;
        $arr['--font-size'] = $this->fontSize;

        return json_encode($arr);
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            '--color-fg' => $this->fgColor,
            '--color-bg' => $this->bgColor,
            '--bg-image' => $this->bgImage ? 'url("' . image_storage_url($this->bgImage) . '")' : '',
            '--color-highlight' => $this->highlightColor,
            '--font-family' => $this->fontFamily,
            '--font-size' => $this->fontSize ? "{$this->fontSize}px" : '13.0px',
        ];
    }
}
