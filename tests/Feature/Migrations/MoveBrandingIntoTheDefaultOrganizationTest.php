<?php

namespace Tests\Feature\Migrations;

use App\Models\Organization;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MoveBrandingIntoTheDefaultOrganizationTest extends TestCase
{
    #[Test]
    public function moveTheInstallWideBrandingIntoTheDefaultOrganization(): void
    {
        DB::table('settings')->insert(['key' => 'branding', 'value' => json_encode(['name' => 'Little Bird'])]);

        $migration = require
            database_path('migrations/2026_09_27_120000_move_branding_into_the_default_organization.php');

        $migration->up();

        self::assertNull(Setting::get('branding'));
        self::assertSame('Little Bird', Setting::get('branding', Organization::default())['name']);
    }
}
