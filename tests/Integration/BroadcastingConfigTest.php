<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BroadcastingConfigTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $originalEnv = [];

    protected function tearDown(): void
    {
        foreach ($this->originalEnv as $name => $value) {
            self::setEnv($name, $value);
        }

        parent::tearDown();
    }

    private function useEnv(string $name, string|false $value): void
    {
        $this->originalEnv[$name] ??= getenv($name);
        self::setEnv($name, $value);
    }

    private static function setEnv(string $name, string|false $value): void
    {
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
