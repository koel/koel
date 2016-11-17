<?php

namespace E2E;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class KoelTest extends TestCase
{
    public function testDefaults()
    {
        static::assertContains('Koel', $this->driver->getTitle());

        $formSelector = '#app > div.login-wrapper > form';

        // Our login form should be there.
        static::assertCount(1, $this->els($formSelector));

        // We submit rubbish and expect an error class on the form.
        $this->login('foo@bar.com', 'ThisIsWongOnSoManyLevels');
        $this->waitUntil(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::cssSelector("$formSelector.error")
        ));

        // Now we submit good stuff and make sure we're in.
        $this->login();
        $this->waitUntil(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('#userBadge > a.view-profile.control > span'), 'Koel Admin'
        ));

        // Default URL must be Home
        static::assertEquals($this->url.'/#!/home', $this->driver->getCurrentURL());

        // While we're at this, test logging out as well.
        $this->click('#userBadge > a.logout');
        $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated(
           WebDriverBy::cssSelector($formSelector)
        ));
    }
}
