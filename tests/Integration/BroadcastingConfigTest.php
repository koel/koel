<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BroadcastingConfigTest extends TestCase
{
    /**
     * The original value of each changed variable in each place PHP keeps it, or null where it was missing.
     *
     * @var array<string, array{getenv: string|false, env: ?array{0: mixed}, server: ?array{0: mixed}}>
     */
    private array $originalEnv = [];

    protected function tearDown(): void
    {
        foreach ($this->originalEnv as $name => $original) {
            if ($original['getenv'] === false) {
                putenv($name);
            } else {
                putenv("$name={$original['getenv']}");
            }

            if ($original['env'] === null) {
                unset($_ENV[$name]);
            } else {
                $_ENV[$name] = $original['env'][0];
            }

            if ($original['server'] === null) {
                unset($_SERVER[$name]);
            } else {
                $_SERVER[$name] = $original['server'][0];
            }
        }

        parent::tearDown();
    }

    private function useEnv(string $name, string|false $value): void
    {
        $this->originalEnv[$name] ??= [
            'getenv' => getenv($name),
            'env' => array_key_exists($name, $_ENV) ? [$_ENV[$name]] : null,
            'server' => array_key_exists($name, $_SERVER) ? [$_SERVER[$name]] : null,
        ];

        if ($value === false) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);

            return;
        }

        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    private static function defaultBroadcaster(): string
    {
        return (require config_path('broadcasting.php'))['default'];
    }

    #[Test]
    public function broadcastThroughPusherWhenItsKeysAreSetUpWithoutAConnection(): void
    {
        $this->useEnv('BROADCAST_CONNECTION', false);
        $this->useEnv('PUSHER_APP_KEY', 'pusher-key');

        self::assertSame('pusher', self::defaultBroadcaster());
    }

    #[Test]
    public function broadcastNowhereWithoutPusherKeysOrAConnection(): void
    {
        $this->useEnv('BROADCAST_CONNECTION', false);
        $this->useEnv('PUSHER_APP_KEY', false);

        self::assertSame('null', self::defaultBroadcaster());
    }

    #[Test]
    public function keepTheConnectionThatIsSetExplicitly(): void
    {
        $this->useEnv('BROADCAST_CONNECTION', 'log');
        $this->useEnv('PUSHER_APP_KEY', 'pusher-key');

        self::assertSame('log', self::defaultBroadcaster());
    }
}
