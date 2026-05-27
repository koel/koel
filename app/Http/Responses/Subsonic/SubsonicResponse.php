<?php

namespace App\Http\Responses\Subsonic;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use SimpleXMLElement;

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
        $envelope = $this->buildEnvelope();

        return match ($request->input('f')) {
            'json' => response()->json(['subsonic-response' => $envelope]),
            'jsonp' => $this->toJsonp($envelope, (string) $request->input('callback', '')),
            default => $this->toXml($envelope),
        };
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
    private function toJsonp(array $envelope, string $callback): Response
    {
        if (!preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*(?:\.[A-Za-z_$][A-Za-z0-9_$]*)*$/', $callback)) {
            $errorBody = json_encode([
                'subsonic-response' => [
                    'status' => 'failed',
                    'version' => self::API_VERSION,
                    'type' => 'koel',
                    'serverVersion' => koel_version(),
                    'openSubsonic' => true,
                    'error' => ['code' => 10, 'message' => 'Required parameter is missing.'],
                ],
            ]);

            return response($errorBody, Response::HTTP_OK, ['Content-Type' => 'application/json']);
        }

        $body = $callback . '(' . json_encode(['subsonic-response' => $envelope]) . ');';

        return response($body, Response::HTTP_OK, ['Content-Type' => 'application/javascript']);
    }

    /** @param array<string, mixed> $data */
    private static function serializeTo(SimpleXMLElement $element, array $data): void
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $element->addAttribute($key, self::formatScalar($value));
            } elseif (Arr::isList($value)) {
                foreach ($value as $item) {
                    $child = $element->addChild($key);
                    self::serializeTo($child, is_array($item) ? $item : ['value' => $item]);
                }
            } else {
                $child = $element->addChild($key);
                self::serializeTo($child, $value);
            }
        }
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
