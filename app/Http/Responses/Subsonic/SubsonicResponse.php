<?php

namespace App\Http\Responses\Subsonic;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SubsonicResponse implements Responsable
{
    public const string API_VERSION = '1.16.1';

    /** @param array<string, mixed> $payload */
    public function __construct(
        private readonly array $payload = [],
        private readonly ?int $errorCode = null,
        private readonly ?string $errorMessage = null,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function ok(array $payload = []): self
    {
        return new self(payload: $payload);
    }

    public static function error(int $code, string $message): self
    {
        return new self(errorCode: $code, errorMessage: $message);
    }

    /** @inheritdoc */
    public function toResponse($request)
    {
        $envelope = self::stripNulls($this->buildEnvelope());

        return match ($request->input('f')) {
            'json' => response()->json(['subsonic-response' => $envelope]),
            'jsonp' => $this->toJsonp($envelope, (string) $request->input('callback', '')),
            default => $this->toXml($envelope),
        };
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function stripNulls(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            $result[$key] = is_array($value) ? self::stripNulls($value) : $value;
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function buildEnvelope(): array
    {
        $envelope = [
            'status' => $this->errorCode === null ? 'ok' : 'failed',
            'version' => self::API_VERSION,
            'type' => 'koel',
            'serverVersion' => koel_version(),
            'openSubsonic' => true,
        ];

        if ($this->errorCode !== null) {
            $envelope['error'] = [
                'code' => $this->errorCode,
                'message' => $this->errorMessage,
            ];
        }

        return $envelope + $this->payload;
    }

    /** @param array<string, mixed> $envelope */
    private function toXml(array $envelope): Response
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><subsonic-response/>');
        self::serializeTo($root, $envelope);

        return response($root->asXML(), Response::HTTP_OK, ['Content-Type' => 'application/xml']);
    }

    /** @param array<string, mixed> $envelope */
    private function toJsonp(array $envelope, string $callback): SymfonyResponse
    {
        try {
            return response()->jsonp($callback, ['subsonic-response' => $envelope]);
        } catch (InvalidArgumentException) {
            $errorEnvelope = self::error(10, 'Required parameter is missing.')->buildEnvelope();

            return response()->json(['subsonic-response' => $errorEnvelope]);
        }
    }

    /** @param array<string, mixed> $data */
    private static function serializeTo(SimpleXMLElement $element, array $data): void
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $element->addAttribute($key, self::formatScalar($value));
            } elseif (Arr::isList($value)) {
                foreach ($value as $item) {
                    self::appendChild($element, $key, is_array($item) ? $item : ['value' => $item]);
                }
            } else {
                self::appendChild($element, $key, $value);
            }
        }
    }

    /** @param array<string, mixed> $data */
    private static function appendChild(SimpleXMLElement $parent, string $key, array $data): void
    {
        $text = $data['value'] ?? null;

        $child = is_scalar($text)
            ? $parent->addChild($key, htmlspecialchars(self::formatScalar($text), ENT_XML1, 'UTF-8'))
            : $parent->addChild($key);

        self::serializeTo($child, Arr::except($data, 'value'));
    }

    private static function formatScalar(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => '',
            default => (string) $value,
        };
    }
}
