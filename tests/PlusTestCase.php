<?php

namespace Tests;

use App\Facades\License;
use App\Services\License\FakePlusLicenseService;
use Tests\TestCase as BaseTestCase;

class PlusTestCase extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        License::swap(app()->make(FakePlusLicenseService::class));
    }
}
