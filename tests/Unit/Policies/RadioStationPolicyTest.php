<?php

namespace Tests\Unit\Policies;

use App\Models\Organization;
use App\Models\RadioStation;
use App\Policies\RadioStationPolicy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class RadioStationPolicyTest extends TestCase
{
    #[Test]
    public function grantAccessToPublicStationsOfTheSameOrganizationOnly(): void
    {
        $user = create_user();
        $stationOfSameOrganization = RadioStation::factory()->for(create_user())->createOne(['is_public' => true]);
        $stationOfOtherOrganization = RadioStation::factory()->for(create_user([
            'organization_id' => Organization::factory(),
        ]))->createOne(['is_public' => true]);

        $policy = new RadioStationPolicy();

        self::assertTrue($policy->access($user, $stationOfSameOrganization));
        self::assertFalse($policy->access($user, $stationOfOtherOrganization));
    }
}
