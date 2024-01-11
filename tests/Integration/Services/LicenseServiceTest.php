<?php

namespace Tests\Integration\Services;

use App\Exceptions\FailedToActivateLicenseException;
use App\Models\License;
use App\Services\ApiClients\ApiClient;
use App\Services\LicenseService;
use App\Values\LicenseStatus;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

use function Tests\test_path;

class LicenseServiceTest extends TestCase
{
    private LicenseService $service;
    private ApiClient|MockInterface|LegacyMockInterface $apiClient;

    public function setUp(): void
    {
        parent::setUp();

        $this->apiClient = $this->mock(ApiClient::class);
        $this->service = app(LicenseService::class);
    }

    public function testActivateLicense(): void
    {
        config(['lemonsqueezy.store_id' => 42]);
        $key = '38b1460a-5104-4067-a91d-77b872934d51';

        $this->apiClient
            ->shouldReceive('post')
            ->with('licenses/activate', [
                'license_key' => $key,
                'instance_name' => 'Koel Plus',
            ])
            ->once()
            ->andReturn(json_decode(File::get(test_path('blobs/lemonsqueezy/license-activated-successful.json'))));

        $license = $this->service->activate($key);

        self::assertSame($key, $license->key);
        self::assertNotNull($license->instance);
        self::assertSame('Luke Skywalker', $license->meta->customerName);
        self::assertSame('luke@skywalker.com', $license->meta->customerEmail);

        /** @var LicenseStatus $cachedLicenseStatus */
        $cachedLicenseStatus = Cache::get('license_status');

        self::assertSame($license->key, $cachedLicenseStatus->license->key);
        self::assertTrue($cachedLicenseStatus->isValid());
    }

    public function testActivateLicenseFailsBecauseOfIncorrectStoreId(): void
    {
        self::expectException(FailedToActivateLicenseException::class);
        self::expectExceptionMessage('This license key is not from Koel’s official store.');

        config(['lemonsqueezy.store_id' => 43]);
        $key = '38b1460a-5104-4067-a91d-77b872934d51';

        $this->apiClient
            ->shouldReceive('post')
            ->with('licenses/activate', [
                'license_key' => $key,
                'instance_name' => 'Koel Plus',
            ])
            ->once()
            ->andReturn(json_decode(File::get(test_path('blobs/lemonsqueezy/license-activated-successful.json'))));

        $this->service->activate($key);
    }

    public function testActivateLicenseFailsForInvalidLicenseKey(): void
    {
        self::expectException(FailedToActivateLicenseException::class);
        self::expectExceptionMessage('license_key not found');

        $exception = Mockery::mock(ClientException::class, [
            'getResponse' => Mockery::mock(ResponseInterface::class, [
                'getBody' => File::get(test_path('blobs/lemonsqueezy/license-invalid.json')),
                'getStatusCode' => Response::HTTP_NOT_FOUND,
            ]),
        ]);

        $this->apiClient
            ->shouldReceive('post')
            ->with('licenses/activate', [
                'license_key' => 'invalid-key',
                'instance_name' => 'Koel Plus',
            ])
            ->once()
            ->andThrow($exception);

        $this->service->activate('invalid-key');
    }

    public function testDeactivateLicense(): void
    {
        /** @var License $license */
        $license = License::factory()->create();

        $this->apiClient
            ->shouldReceive('post')
            ->with('licenses/deactivate', [
                'license_key' => $license->key,
                'instance_id' => $license->instance->id,
            ])
            ->once()
            ->andReturn(json_decode(File::get(test_path('blobs/lemonsqueezy/license-deactivated-successful.json'))));

        $this->service->deactivate($license);

        self::assertModelMissing($license);
        self::assertFalse(Cache::has('license_status'));
    }

    public function testDeactivateLicenseHandlesLeftoverRecords(): void
    {
        /** @var License $license */
        $license = License::factory()->create();

        $exception = Mockery::mock(ClientException::class, [
            'getResponse' => Mockery::mock(ResponseInterface::class, [
                'getStatusCode' => Response::HTTP_NOT_FOUND,
            ]),
        ]);

        $this->apiClient
            ->shouldReceive('post')
            ->with('licenses/deactivate', [
                'license_key' => $license->key,
                'instance_id' => $license->instance->id,
            ])
            ->once()
            ->andThrow($exception);

        $this->service->deactivate($license);

        self::assertModelMissing($license);
    }

    public function testDeactivateLicenseFails(): void
    {
        self::expectExceptionMessage('Something went horribly wrong');

        /** @var License $license */
        $license = License::factory()->create();

        $this->apiClient
            ->shouldReceive('post')
            ->with('licenses/deactivate', [
                'license_key' => $license->key,
                'instance_id' => $license->instance->id,
            ])
            ->once()
            ->andThrow(new Exception('Something went horribly wrong'));

        $this->service->deactivate($license);
    }

    public function testGetLicenseStatusFromCache(): void
    {
        /** @var License $license */
        $license = License::factory()->create();

        Cache::put('license_status', LicenseStatus::valid($license));

        $this->apiClient->shouldNotReceive('post');

        self::assertTrue($this->service->getStatus()->license->is($license));
        self::assertTrue($this->service->getStatus()->isValid());
    }

    public function testGetLicenseStatusWithNoLicenses(): void
    {
        License::query()->delete();

        $this->apiClient->shouldNotReceive('post');

        self::assertTrue($this->service->getStatus()->hasNoLicense());
    }

    public function testGetLicenseStatusValidatesWithApi(): void
    {
        /** @var License $license */
        $license = License::factory()->create();

        self::assertFalse(Cache::has('license_status'));

        $this->apiClient
            ->shouldReceive('post')
            ->with('licenses/validate', [
                'license_key' => $license->key,
                'instance_id' => $license->instance->id,
            ])
            ->once()
            ->andReturn(json_decode(File::get(test_path('blobs/lemonsqueezy/license-validated-successful.json'))));

        self::assertTrue($this->service->getStatus()->isValid());
        self::assertTrue(Cache::has('license_status'));
    }

    public function testGetLicenseStatusValidatesWithApiWithInvalidLicense(): void
    {
        /** @var License $license */
        $license = License::factory()->create();

        self::assertFalse(Cache::has('license_status'));

        $exception = Mockery::mock(ClientException::class, [
            'getResponse' => Mockery::mock(ResponseInterface::class, [
                'getStatusCode' => Response::HTTP_NOT_FOUND,
            ]),
        ]);

        $this->apiClient
            ->shouldReceive('post')
            ->with('licenses/validate', [
                'license_key' => $license->key,
                'instance_id' => $license->instance->id,
            ])
            ->once()
            ->andThrow($exception);

        self::assertFalse($this->service->getStatus()->isValid());
        self::assertTrue(Cache::has('license_status'));
    }
}
