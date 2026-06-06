<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\AddRadioStation;
use App\Models\RadioStation;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class AddRadioStationToolTest extends PlusTestCase
{
    private User $user;
    private AddRadioStation $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();
        $result = new AiAssistantResult();

        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(AddRadioStation::class);
    }

    #[Test]
    public function addsRadioStationWithSafePublicUrl(): void
    {
        Http::fake([
            'https://example.com/stream.mp3' => Http::response('', 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        $response = $this->tool->handle(new Request([
            'name' => 'Test Radio',
            'url' => 'https://example.com/stream.mp3',
        ]));

        self::assertStringContainsString('Test Radio', (string) $response);
        self::assertStringContainsString('added successfully', (string) $response);

        $this->assertDatabaseHas(RadioStation::class, [
            'url' => 'https://example.com/stream.mp3',
            'name' => 'Test Radio',
            'user_id' => $this->user->id,
        ]);
    }

    /** @return array<string, array{string}> */
    public static function provideUnsafeUrls(): array
    {
        return [
            'AWS metadata IP' => ['http://169.254.169.254/latest/meta-data/'],
            'loopback IPv4' => ['http://127.0.0.1/admin'],
            'private 192.168.x' => ['http://192.168.1.1/secrets'],
            'private 10.x' => ['http://10.0.0.1/internal'],
            'file scheme' => ['file:///etc/passwd'],
            'ftp scheme' => ['ftp://example.com/stream'],
        ];
    }

    #[Test, DataProvider('provideUnsafeUrls')]
    public function refusesUnsafeUrl(string $unsafeUrl): void
    {
        Http::fake();

        $response = $this->tool->handle(new Request([
            'name' => 'SSRF Attempt',
            'url' => $unsafeUrl,
        ]));

        self::assertStringContainsString('Cannot add radio station', (string) $response);
        $this->assertDatabaseMissing(RadioStation::class, ['url' => $unsafeUrl]);
    }

    #[Test]
    public function refusesDuplicateUrlForSameUser(): void
    {
        Http::fake([
            'https://example.com/stream.mp3' => Http::response('', 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        RadioStation::factory()->for($this->user)->createOne(['url' => 'https://example.com/stream.mp3']);

        $response = $this->tool->handle(new Request([
            'name' => 'Duplicate',
            'url' => 'https://example.com/stream.mp3',
        ]));

        self::assertStringContainsString('Cannot add radio station', (string) $response);
    }
}
