<?php

namespace App\Values;

use App\Facades\License;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;

class EmbedOptions implements Arrayable
{
    private function __construct(public string $theme, public string $layout, public bool $preview)
    {
        // Preview mode and theme are only customizable in Koel Plus
        if (License::isCommunity()) {
            $this->theme = 'classic';
            $this->preview = false;
        }
    }

    public static function make(string $theme = 'classic', string $layout = 'full', bool $preview = false): self
    {
        return new self($theme, $layout, $preview);
    }

    public static function fromEncrypted(string $encrypted): self
    {
        try {
            $array = decrypt($encrypted);

            return new self(
                theme: Arr::get($array, 'theme', 'classic'),
                layout: Arr::get($array, 'layout', 'full'),
                preview: Arr::get($array, 'preview', false),
            );
        } catch (Throwable) {
            return self::make();
        }
    }

    public static function fromRequest(Request $request): self
    {
        return self::fromEncrypted($request->route('options'));
    }

    public function encrypt(): string
    {
        $array = $this->toArray();
        ksort($array);

        // Remove the default values from the array to keep the payload minimal
        foreach (self::make()->toArray() as $key => $value) {
            if ($array[$key] === $value) {
                unset($array[$key]);
            }
        }

        return encrypt($array);
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'theme' => $this->theme,
            'layout' => $this->layout,
            'preview' => $this->preview,
        ];
    }

    public function __toString(): string
    {
        return $this->encrypt();
    }
}
