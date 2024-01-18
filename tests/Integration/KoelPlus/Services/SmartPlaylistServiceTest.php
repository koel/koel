<?php

namespace Tests\Integration\KoelPlus\Services;

use App\Facades\License;
use App\Models\Song;
use App\Services\License\FakePlusLicenseService;
use Tests\Integration\Services\SmartPlaylistServiceTest as BaseSmartPlaylistServiceTest;

use function Tests\create_user;

class SmartPlaylistServiceTest extends BaseSmartPlaylistServiceTest
{
    public function setUp(): void
    {
        parent::setUp();

        License::swap(app()->make(FakePlusLicenseService::class));
    }

    public function testOwnSongsOnlyOption(): void
    {
        $owner = create_user();
        $matches = Song::factory()->count(3)->for($owner, 'owner')->create(['title' => 'Foo Something']);
        Song::factory()->count(2)->create(['title' => 'Foo Something']);
        Song::factory()->count(3)->create(['title' => 'Bar Something']);

        $this->assertMatchesAgainstRules(
            matches: $matches,
            rules: [
                [
                    'id' => 'aaf61bc3-3bdf-4fa4-b9f3-f7f7838ed502',
                    'rules' => [
                        [
                            'id' => '70b08372-b733-4fe2-aedb-639f77120d6d',
                            'model' => 'title',
                            'operator' => 'is',
                            'value' => ['Foo Something'],
                        ],
                    ],
                ],
            ],
            owner: $owner,
            ownSongsOnly: true
        );
    }
}
