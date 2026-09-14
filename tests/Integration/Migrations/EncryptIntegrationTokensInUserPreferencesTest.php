<?php

namespace Tests\Integration\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class EncryptIntegrationTokensInUserPreferencesTest extends TestCase
{
    #[Test]
    public function plainTextTokensAreEncryptedInPlace(): void
    {
        $user = create_user();

        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'preferences' => json_encode([
                    'lastfm_session_key' => 'lastfm-secret',
                    'listenbrainz_token' => 'listenbrainz-secret',
                    'theme' => 'classic',
                ]),
            ]);

        $this->runMigration();

        $stored = json_decode(DB::table('users')->where('id', $user->id)->value('preferences'), true);

        self::assertSame('lastfm-secret', Crypt::decryptString($stored['lastfm_session_key']));
        self::assertSame('listenbrainz-secret', Crypt::decryptString($stored['listenbrainz_token']));
        self::assertSame('classic', $stored['theme']);
        self::assertSame('lastfm-secret', $user->refresh()->preferences->lastFmSessionKey);
    }

    #[Test]
    public function usersWithoutTokensAreLeftAlone(): void
    {
        $user = create_user();
        $preferences = json_encode(['theme' => 'classic']);

        DB::table('users')->where('id', $user->id)->update(['preferences' => $preferences]);

        $this->runMigration();

        self::assertSame($preferences, DB::table('users')->where('id', $user->id)->value('preferences'));
    }

    private function runMigration(): void
    {
        /** @var Migration $migration */
        $migration = require
            database_path('migrations/2026_09_15_090000_encrypt_integration_tokens_in_user_preferences.php');

        $migration->up(); // @phpstan-ignore-line
    }
}
