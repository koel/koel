<?php

namespace Tests;

use App\Facades\License;
use Tests\Fakes\FakePlusLicenseService;
use Tests\TestCase as BaseTestCase;

class PlusTestCase extends BaseTestCase
{
    public static function enablePlusLicense(): void
    {
        License::swap(app(FakePlusLicenseService::class));
    }

    public function setUp(): void
    {
        parent::setUp();

        self::enablePlusLicense();
    }
}
