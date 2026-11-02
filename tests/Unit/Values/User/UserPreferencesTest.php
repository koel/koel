<?php

namespace Tests\Unit\Values\User;

use App\Values\User\Preferences\BooleanPreference;
use App\Values\User\Preferences\VolumePreference;
use App\Values\User\UserPreferences;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fakes\FakeExtensionPreference;
use Tests\TestCase;
use Webmozart\Assert\InvalidArgumentException;

class UserPreferencesTest extends TestCase
{
    protected function tearDown(): void
    {
        UserPreferences::forgetRegisteredPreferences();

        parent::tearDown();
    }

    #[Test]
    public function registeredPreferenceReadsItsDefaultEvenAfterPreferencesWereUsed(): void
    {
        UserPreferences::fromArray([]);

        UserPreferences::register(FakeExtensionPreference::class);
        $preferences = UserPreferences::fromArray([]);

        self::assertTrue($preferences->toArray()['fake_extension']);
    }

    #[Test]
    public function refuseToRegisterAnAbstractPreferenceClass(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UserPreferences::register(BooleanPreference::class);
    }

    #[Test]
    public function refuseToRegisterAPreferenceWhoseKeyIsTaken(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UserPreferences::register(VolumePreference::class);
    }
}
