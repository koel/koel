<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class ProfileTest extends TestCase
{
    #[Test]
    public function updateProfile(): void
    {
        $user = create_user();

        $this->putAs(
            'api/me',
            [
                'name' => 'Foo',
                'email' => 'bar@baz.com',
            ],
            $user,
        )->assertOk();

        $user->refresh();

        self::assertSame('Foo', $user->name);
        self::assertSame('bar@baz.com', $user->email);
    }

    #[Test]
    public function updateProfileWithAvatar(): void
    {
        $user = create_user();
        self::assertNull($user->getRawOriginal('avatar'));

        $this->putAs(
            'api/me',
            [
                'name' => 'Foo',
                'email' => 'bar@baz.com',
                'avatar' => minimal_base64_encoded_image(),
            ],
            $user,
        )->assertOk();

        $user->refresh();

        self::assertFileExists(image_storage_path($user->getRawOriginal('avatar')));
    }

    #[Test]
    public function updateProfileKeepingAvatar(): void
    {
        $user = create_user(['avatar' => 'foo.jpg']);

        $this->putAs(
            'api/me',
            [
                'name' => 'Foo',
                'email' => 'bar@baz.com',
            ],
            $user,
        )->assertOk();

        $user->refresh();

        self::assertSame('foo.jpg', $user->getRawOriginal('avatar'));
    }

    #[Test]
    public function updateProfileRemovingAvatar(): void
    {
        $user = create_user(['avatar' => 'foo.jpg']);

        $this->putAs(
            'api/me',
            [
                'name' => 'Foo',
                'email' => $user->email,
                'avatar' => null,
            ],
            $user,
        )->assertOk();

        $user->refresh();

        self::assertNull($user->getRawOriginal('avatar'));
    }

    /** @return array<string, array<string>> */
    public static function avatarsThatAreNotImageDataProvider(): array
    {
        return [
            'remote URL' => ['https://example.com/avatar.jpg'],
            'non-image data URL' => ['data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='],
        ];
    }

    #[Test]
    #[DataProvider('avatarsThatAreNotImageDataProvider')]
    public function updateProfileRejectsAvatarThatIsNotImageData(string $avatar): void
    {
        $user = create_user(['avatar' => 'foo.jpg']);

        $this->putAs(
            'api/me',
            [
                'name' => 'Foo',
                'email' => $user->email,
                'avatar' => $avatar,
            ],
            $user,
        )->assertUnprocessable();

        self::assertSame('foo.jpg', $user->refresh()->getRawOriginal('avatar'));
    }

    #[Test]
    public function disabledInDemo(): void
    {
        config(['koel.misc.demo' => true]);
        $user = create_user(['name' => 'Original', 'email' => 'original@example.com']);

        $this->putAs(
            'api/me',
            [
                'name' => 'Foo',
                'email' => 'bar@baz.com',
            ],
            $user,
        )->assertNoContent();

        $user->refresh();
        self::assertSame('Original', $user->name);
        self::assertSame('original@example.com', $user->email);
    }
}
