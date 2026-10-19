<?php

namespace Tests\Feature\KoelPlus\Subsonic;

use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_manager;
use function Tests\create_user;

class GetUserTest extends PlusTestCase
{
    #[Test]
    public function letAManagerQueryOtherUsers(): void
    {
        $manager = create_manager();
        $other = create_user();

        $this->getJson(
            "/rest/getUser.view?apiKey={$manager->subsonic_api_key}&f=json&username=" . urlencode($other->email),
        )->assertJsonPath('subsonic-response.user.username', $other->email);
    }
}
