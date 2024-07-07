<?php

namespace Tests\Integration\Services;

use App\Exceptions\FailedToActivateLicenseException;
use App\Http\Integrations\LemonSqueezy\Requests\ActivateLicenseRequest;
use App\Http\Integrations\LemonSqueezy\Requests\DeactivateLicenseRequest;
use App\Http\Integrations\LemonSqueezy\Requests\ValidateLicenseRequest;
use App\Models\License;
use App\Services\LicenseService;
use App\Values\LicenseStatus;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;
use Tests\TestCase;

use function Tests\test_path;

class LicenseServiceTest extends TestCase
{
    private LicenseService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(LicenseService::class);
    }

    public function testActivateLicense(): void
    {
        config(['lemonsqueezy.store_id' => 42]);
        $key = '38b1460a-5104-4067-a91d-77b872934d51';

        Saloon::fake([
            ActivateLicenseRequest::class => MockResponse::make(
                body: File::get(test_path('blobs/lemonsqueezy/license-activated-successful.json')),
            ),
        ]);

        $license = $this->service->activate($key);

        self::assertSame($key, $license->key);
        self::assertNotNull($license->instance);
        self::assertSame('Luke Skywalker', $license->meta->customerName);
        self::assertSame('luke@skywalker.com', $license->meta->customerEmail);

        /** @var LicenseStatus $cachedLicenseStatus */
        $cachedLicenseStatus = Cache::get('license_status');

        self::assertSame($license->key, $cachedLicenseStatus->license->key);
        self::assertTrue($cachedLicenseStatus->isValid());

        Saloon::assertSent(static function (ActivateLicenseRequest $request) use ($key): bool {
            self::assertSame([
                'license_key' => $key,
                'instance_name' => 'Koel Plus',
            ], $request->body()->all());

            return true;
        });
    }

    public function testActivateLicenseFailsBecauseOfIncorrectStoreId(): void
    {
        $this->expectException(FailedToActivateLicenseException::class);
        $this->expectExceptionMessage('This license key is not from Koel’s official store.');

        config(['lemonsqueezy.store_id' => 43]);
        $key = '38b1460a-5104-4067-a91d-77b872934d51';

        Saloon::fake([
            ActivateLicenseRequest::class => MockResponse::make(
                body: File::get(test_path('blobs/lemonsqueezy/license-activated-successful.json')),
            ),
        ]);

        $this->service->activate($key);
    }

    public function testActivateLicenseFailsForInvalidLicenseKey(): void
    {
        $this->expectException(FailedToActivateLicenseException::class);
        $this->expectExceptionMessage('license_key not found');

        Saloon::fake([
            ActivateLicenseRequest::class => MockResponse::make(
                body: File::get(test_path('blobs/lemonsqueezy/license-invalid.json')),
                status: Response::HTTP_NOT_FOUND,
            ),
        ]);

        $this->service->activate('invalid-key');
    }

    public function testDeactivateLicense(): void
    {
        /** @var License $license */
        $license = License::factory()->create();

        Saloon::fake([
            DeactivateLicenseRequest::class => MockResponse::make(
                body: File::get(test_path('blobs/lemonsqueezy/license-deactivated-successful.json')),
                status: Response::HTTP_NOT_FOUND,
            ),
        ]);

        $this->service->deactivate($license);

        self::assertModelMissing($license);
        self::assertFalse(Cache::has('license_status'));

        Saloon::assertSent(static function (DeactivateLicenseRequest $request) use ($license): bool {
            self::assertSame([
                'license_key' => $license->key,
                'instance_id' => $license->instance->id,
            ], $request->body()->all());

            return true;
        });
    }

    public function testDeactivateLicenseHandlesLeftoverRecords(): void
    {
        /** @var License $license */
        $license = License::factory()->create();
        Saloon::fake([DeactivateLicenseRequest::class => MockResponse::make(status: Response::HTTP_NOT_FOUND)]);

        $this->service->deactivate($license);

        self::assertModelMissing($license);
    }

    public function testDeactivateLicenseFails(): void
    {
        $this->expectExceptionMessage('Unprocessable Entity (422) Response: Something went horrible wrong');

        /** @var License $license */
        $license = License::factory()->create();

        Saloon::fake([
            DeactivateLicenseRequest::class => MockResponse::make(
                body: 'Something went horrible wrong',
                status: Response::HTTP_UNPROCESSABLE_ENTITY
            ),
        ]);

        $this->service->deactivate($license);
    }

    public function testGetLicenseStatusFromCache(): void
    {
        Saloon::fake([]);

        /** @var License $license */
        $license = License::factory()->create();

        Cache::put('license_status', LicenseStatus::valid($license));

        self::assertTrue($this->service->getStatus()->license->is($license));
        self::assertTrue($this->service->getStatus()->isValid());

        Saloon::assertNothingSent();
    }

    public function testGetLicenseStatusWithNoLicenses(): void
    {
        Saloon::fake([]);
        License::query()->delete();

        self::assertTrue($this->service->getStatus()->hasNoLicense());
        Saloon::assertNothingSent();
    }

    public function testGetLicenseStatusValidatesWithApi(): void
    {
        /** @var License $license */
        $license = License::factory()->create();

        self::assertFalse(Cache::has('license_status'));

        Saloon::fake([
            ValidateLicenseRequest::class => MockResponse::make(
                body: File::get(test_path('blobs/lemonsqueezy/license-validated-successful.json')),
            ),
        ]);

        self::assertTrue($this->service->getStatus()->isValid());
        self::assertTrue(Cache::has('license_status'));

        Saloon::assertSent(static function (ValidateLicenseRequest $request) use ($license): bool {
            self::assertSame([
                'license_key' => $license->key,
                'instance_id' => $license->instance->id,
            ], $request->body()->all());

            return true;
        });
    }

    public function testGetLicenseStatusValidatesWithApiWithInvalidLicense(): void
    {
        License::factory()->create();

        self::assertFalse(Cache::has('license_status'));

        Saloon::fake([ValidateLicenseRequest::class => MockResponse::make(status: Response::HTTP_NOT_FOUND)]);

        self::assertFalse($this->service->getStatus()->isValid());
        self::assertTrue(Cache::has('license_status'));
    }
}
