<?php

namespace Tests\Feature\Commands;

use App\Models\License;
use App\Services\License\Contracts\LicenseServiceInterface;
use App\Values\License\LicenseStatus;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivateLicenseCommandTest extends TestCase
{
    #[Test]
    public function deactivateALicense(): void
    {
        $license = License::factory()->createOne();

        $licenseService = Mockery::mock(LicenseServiceInterface::class);
        $licenseService->shouldReceive('getStatus')->once()->andReturn(LicenseStatus::valid($license));

        $licenseService
            ->shouldReceive('deactivate')
            ->with(Mockery::on(static fn (License $l) => $l->id === $license->id))
            ->once();

        $this->app->instance(LicenseServiceInterface::class, $licenseService);

        $this
            ->artisan('koel:license:deactivate')
            ->expectsConfirmation('Are you sure you want to deactivate your Koel Plus license?', 'yes')
            ->assertSuccessful();
    }

    #[Test]
    public function abortWhenUserDeclinesConfirmation(): void
    {
        $license = License::factory()->createOne();

        $licenseService = Mockery::mock(LicenseServiceInterface::class);
        $licenseService->shouldReceive('getStatus')->once()->andReturn(LicenseStatus::valid($license));

        $licenseService->shouldReceive('deactivate')->never();

        $this->app->instance(LicenseServiceInterface::class, $licenseService);

        $this
            ->artisan('koel:license:deactivate')
            ->expectsConfirmation('Are you sure you want to deactivate your Koel Plus license?', 'no')
            ->assertSuccessful();
    }

    #[Test]
    public function handleNoActiveLicense(): void
    {
        $licenseService = Mockery::mock(LicenseServiceInterface::class);
        $licenseService->shouldReceive('getStatus')->once()->andReturn(LicenseStatus::noLicense());

        $licenseService->shouldReceive('deactivate')->never();

        $this->app->instance(LicenseServiceInterface::class, $licenseService);

        $this->artisan('koel:license:deactivate')->assertSuccessful();
    }

    #[Test]
    public function handleDeactivationFailure(): void
    {
        $license = License::factory()->createOne();

        $licenseService = Mockery::mock(LicenseServiceInterface::class);
        $licenseService->shouldReceive('getStatus')->once()->andReturn(LicenseStatus::valid($license));

        $licenseService->shouldReceive('deactivate')->once()->andThrow(new \RuntimeException('API error'));

        $this->app->instance(LicenseServiceInterface::class, $licenseService);

        $this
            ->artisan('koel:license:deactivate')
            ->expectsConfirmation('Are you sure you want to deactivate your Koel Plus license?', 'yes')
            ->assertFailed();
    }
}
