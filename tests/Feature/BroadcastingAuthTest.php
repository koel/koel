<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class BroadcastingAuthTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'pusher-key',
            'broadcasting.connections.pusher.secret' => 'pusher-secret',
            'broadcasting.connections.pusher.app_id' => 'pusher-app-id',
            'broadcasting.connections.pusher.options.cluster' => 'eu',
        ]);

        self::registerChannelsOnTheNowDefaultConnection();
    }

    private static function registerChannelsOnTheNowDefaultConnection(): void
    {
        require base_path('routes/channels.php');
    }

    #[Test]
    public function signsTheChannelOwnedByTheUser(): void
    {
        $user = create_user();

        $this->postAs(
            'api/broadcasting/auth',
            [
                'channel_name' => "private-user.$user->public_id",
                'socket_id' => '1234.5678',
            ],
            $user,
        )->assertOk();
    }

    #[Test]
    public function refusesToSignAnotherUsersChannel(): void
    {
        $someoneElse = create_user();

        $this->postAs(
            'api/broadcasting/auth',
            [
                'channel_name' => "private-user.$someoneElse->public_id",
                'socket_id' => '1234.5678',
            ],
            create_user(),
        )->assertForbidden();
    }

    #[Test]
    public function signsTheUsersOwnRemoteControlChannel(): void
    {
        $user = create_user();

        $this->postAs(
            'api/broadcasting/auth',
            [
                'channel_name' => "private-koel.$user->public_id",
                'socket_id' => '1234.5678',
            ],
            $user,
        )->assertOk();
    }

    #[Test]
    public function refusesToSignAnotherUsersRemoteControlChannel(): void
    {
        $someoneElse = create_user();

        $this->postAs(
            'api/broadcasting/auth',
            [
                'channel_name' => "private-koel.$someoneElse->public_id",
                'socket_id' => '1234.5678',
            ],
            create_user(),
        )->assertForbidden();
    }
}
