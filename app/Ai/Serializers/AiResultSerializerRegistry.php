<?php

namespace App\Ai\Serializers;

use App\Ai\AiAssistantResult;
use App\Ai\Serializers\Contracts\AiResultSerializer;
use Symfony\Component\Finder\Finder;

class AiResultSerializerRegistry
{
    /** @var array<class-string<AiResultSerializer>>|null */
    private static ?array $serializers = null;

    public static function serialize(AiAssistantResult $result): array
    {
        foreach (self::collectSerializers() as $serializer) {
            if ($serializer::supports($result)) {
                return $serializer::serialize($result);
            }
        }

        return [];
    }

    /** @return array<class-string<AiResultSerializer>> */
    private static function collectSerializers(): array
    {
        return self::$serializers ??= collect(
            Finder::create()
                ->files()
                ->name('*.php')
                ->depth(0)
                ->in(__DIR__),
        )
            ->map(static fn ($file) => __NAMESPACE__ . '\\' . $file->getBasename('.php'))
            ->filter(static fn (string $class) => is_subclass_of($class, AiResultSerializer::class))
            ->values()
            ->all();
    }
}
