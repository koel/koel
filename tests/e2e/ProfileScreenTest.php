<?php

namespace E2E;

use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;

class ProfileScreenTest extends TestCase
{
    public function testProfileScreen()
    {
        $this->loginAndWait();
        $this->click('a.view-profile');
        $this->see('#profileWrapper');
        // Now we change some user profile details
        $this->typeIn('#profileWrapper input[name="name"]', 'Mr Bar');
        $this->typeIn('#profileWrapper input[name="email"]', 'bar@koel.net');
        $this->enter();
        $this->see('.sweet-alert');
        // Dismiss the alert first
        $this->press(WebDriverKeys::ESCAPE);
        $this->notSee('.sweet-alert');

        $avatar = $this->el('a.view-profile img');
        // Expect the Gravatar to be updated
        static::assertEquals('https://www.gravatar.com/avatar/36df72b4484fed183fad058f30b55d21?s=256', $avatar->getAttribute('src'));

        // Check "Confirm Closing" and validate its functionality
        $this->click('#profileWrapper input[name="confirmClosing"]');
        $this->refresh();
        $this->waitUntil(WebDriverExpectedCondition::alertIsPresent());
        $this->driver->switchTo()->alert()->dismiss();

        // Reverse all changes for other tests to not be affected
        $this->typeIn('#profileWrapper input[name="name"]', 'Koel Admin');
        $this->typeIn('#profileWrapper input[name="email"]', 'koel@example.com');
        $this->enter();
        $this->see('.sweet-alert');
        $this->press(WebDriverKeys::ESCAPE);
        $this->notSee('.sweet-alert');
        $this->click('#profileWrapper input[name="confirmClosing"]');
    }
}
