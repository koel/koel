<?php

namespace Tests\Feature;

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
    public function updateProfileRemovingAvatar(): void
    {
        $user = create_user(['avatar' => 'foo.jpg']);

        $this->putAs(
            'api/me',
            [
                'name' => 'Foo',
                'email' => $user->email,
            ],
            $user,
        )->assertOk();

        $user->refresh();

        self::assertNull($user->getRawOriginal('avatar'));
    }

    #[Test]
    public function disabledInDemo(): void
    {
        config(['koel.misc.demo' => true]);
        $user = create_user();

        $this->putAs(
            'api/me',
            [
                'name' => 'Foo',
                'email' => 'bar@baz.com',
            ],
            $user,
        )->assertNoContent();
    }
}
