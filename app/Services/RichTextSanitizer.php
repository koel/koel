<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Arr;

class RichTextSanitizer
{
    private const string ALLOWED_MARKUP = 'p,br,strong,b,em,i,u,s,h2,h3,ul,ol,li,a[href]';

    /**
     * Keep only the simple formatting the description editor offers, and return null when nothing readable is left.
     */
    public function sanitize(string $html): ?string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', self::ALLOWED_MARKUP);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('Cache.DefinitionImpl', null);

        $sanitized = trim((new HTMLPurifier($config))->purify($html));

        return trim(strip_tags($sanitized)) === '' ? null : $sanitized;
    }

    /**
     * @param array<string, mixed> $attributes
     * @param ?string $html null leaves the attribute unchanged
     *
     * @return array<string, mixed>
     */
    public function applySanitizedToAttributes(array $attributes, string $attribute, ?string $html): array
    {
        if ($html === null) {
            return Arr::except($attributes, $attribute);
        }

        return [...$attributes, $attribute => $this->sanitize($html)];
    }
}
