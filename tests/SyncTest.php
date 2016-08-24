<?php

use App\Models\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class SyncTest extends TestCase
{
    use WithoutMiddleware;

    public function testSyncLibrary()
    {
        Media::shouldReceive('sync')->once();

        $this->actingAs(factory(User::class, 'admin')->create())
            ->post('/api/syncLibrary', [])
            ->seeStatusCode(200);
    }
}
