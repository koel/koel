<?php

namespace Tests\Feature\Commands;

use App\Models\License;
use App\Services\License\Contracts\LicenseServiceInterface;
use App\Values\License\LicenseStatus;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckLicenseStatusCommandTest extends TestCase
{
    #[Test]
    public function showValidLicenseStatus(): void
    {
        $license = License::factory()->createOne();

        $licenseService = Mockery::mock(LicenseServiceInterface::class);
        $licenseService->shouldReceive('getStatus')->once()->andReturn(LicenseStatus::valid($license));

        $this->app->instance(LicenseServiceInterface::class, $licenseService);

        $this->artisan('koel:license:status')->assertSuccessful();
    }

    #[Test]
    public function showNoLicenseStatus(): void
    {
        $licenseService = Mockery::mock(LicenseServiceInterface::class);
        $licenseService->shouldReceive('getStatus')->once()->andReturn(LicenseStatus::noLicense());

        $this->app->instance(LicenseServiceInterface::class, $licenseService);

        $this->artisan('koel:license:status')->assertSuccessful();
    }

    #[Test]
    public function showInvalidLicenseStatus(): void
    {
        $license = License::factory()->createOne();

        $licenseService = Mockery::mock(LicenseServiceInterface::class);
        $licenseService->shouldReceive('getStatus')->once()->andReturn(LicenseStatus::invalid($license));

        $this->app->instance(LicenseServiceInterface::class, $licenseService);

        $this->artisan('koel:license:status')->assertSuccessful();
    }

    #[Test]
    public function warnAboutMultipleLicenses(): void
    {
        License::factory()->createMany(2);

        $licenseService = Mockery::mock(LicenseServiceInterface::class);
        $licenseService->shouldReceive('getStatus')->once()->andReturn(LicenseStatus::noLicense());

        $this->app->instance(LicenseServiceInterface::class, $licenseService);

        $this->artisan('koel:license:status')->assertSuccessful();
    }
}
