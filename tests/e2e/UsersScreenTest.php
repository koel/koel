<?php

namespace E2E;

use Facebook\WebDriver\Interactions\Internal\WebDriverMouseMoveAction;

class UsersScreenTest extends TestCase
{
    public function testUsersScreen()
    {
        $this->loginAndGoTo('users')->see('.user-item.me');
        // Hover to the first user item
        (new WebDriverMouseMoveAction($this->driver->getMouse(), $this->el('#usersWrapper .user-item.me')))
            ->perform();
        // and validate that the button reads "Update Profile" instead of "Edit"
        static::assertContains('Update Profile',
            $this->el('#usersWrapper .user-item.me .btn-edit')->getText());
        // Also, clicking it should show the "Profile & Preferences" panel
        $this->click('#usersWrapper .user-item.me .btn-edit');
        $this->see('#profileWrapper')
            ->back()
            // Add new user
            ->click('#usersWrapper .btn-add');
        $this->see('form.user-add')
            ->typeIn('form.user-add input[name="name"]', 'Foo')
            ->typeIn('form.user-add input[name="email"]', 'foo@koel.net')
            ->typeIn('form.user-add input[name="password"]', 'SecureMuch')
            ->enter()
            ->seeText('foo@koel.net', '#usersWrapper');

        // Hover the next user item (not me)
        (new WebDriverMouseMoveAction($this->driver->getMouse(), $this->el('#usersWrapper .user-item:not(.me)')))
            ->perform();
        static::assertContains('Edit', $this->el('#usersWrapper .user-item:not(.me) .btn-edit')->getText());
        // Edit user
        $this->click('#usersWrapper .user-item:not(.me) .btn-edit');
        $this->see('form.user-edit')
            ->typeIn('form.user-edit input[name="email"]', 'bar@koel.net')
            ->enter()
            ->seeText('bar@koel.net', '#usersWrapper');
    }
}
