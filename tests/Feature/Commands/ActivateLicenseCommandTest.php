<?php

namespace Tests\Feature\Commands;

use App\Models\License;
use App\Services\License\Contracts\LicenseServiceInterface;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ActivateLicenseCommandTest extends TestCase
{
    #[Test]
    public function activateALicense(): void
    {
        $license = License::factory()->create();

        $licenseService = Mockery::mock(LicenseServiceInterface::class);
        $licenseService->shouldReceive('activate')->with('test-license-key')->once()->andReturn($license);

        $this->app->instance(LicenseServiceInterface::class, $licenseService);

        $this->artisan('koel:license:activate', ['key' => 'test-license-key'])->assertSuccessful();
    }

    #[Test]
    public function handleActivationFailure(): void
    {
        $licenseService = Mockery::mock(LicenseServiceInterface::class);
        $licenseService
            ->shouldReceive('activate')
            ->with('invalid-key')
            ->once()
            ->andThrow(new RuntimeException('Invalid license key'));

        $this->app->instance(LicenseServiceInterface::class, $licenseService);

        $this->artisan('koel:license:activate', ['key' => 'invalid-key'])->assertFailed();
    }
}
