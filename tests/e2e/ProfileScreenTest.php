<?php

namespace E2E;

use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;

class ProfileScreenTest extends TestCase
{
    public function testProfileScreen()
    {
        $this->loginAndWait()
            ->click('a.view-profile');
        $this->see('#profileWrapper')
            // Now we change some user profile details
            ->typeIn('#profileWrapper input[name="name"]', 'Mr Bar')
            ->typeIn('#profileWrapper input[name="email"]', 'bar@koel.net')
            ->enter()
            ->see('.alertify-logs')
            // Dismiss the alert first
            ->press(WebDriverKeys::ESCAPE)
            ->notSee('.alertify-logs');

        $avatar = $this->el('a.view-profile img');
        // Expect the Gravatar to be updated
        static::assertEquals('https://www.gravatar.com/avatar/36df72b4484fed183fad058f30b55d21?s=256', $avatar->getAttribute('src'));

        // Check "Confirm Closing" and validate its functionality
        $this->click('#profileWrapper input[name="confirmClosing"]');
        $this->refresh()
            ->waitUntil(WebDriverExpectedCondition::alertIsPresent());
        $this->driver->switchTo()->alert()->dismiss();

        // Reverse all changes for other tests to not be affected
        $this->typeIn('#profileWrapper input[name="name"]', 'Koel Admin')
            ->typeIn('#profileWrapper input[name="email"]', 'koel@example.com')
            ->enter()
            ->see('.alertify-logs')
            ->press(WebDriverKeys::ESCAPE)
            ->notSee('.alertify-logs')
            ->click('#profileWrapper input[name="confirmClosing"]');
    }
}
