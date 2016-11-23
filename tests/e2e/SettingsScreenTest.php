<?php

namespace E2E;

class SettingsScreenTest extends TestCase
{
    public function testSettingsScreen()
    {
        $this->loginAndGoTo('settings');
        $this->typeIn('#inputSettingsPath', dirname(__DIR__.'/../songs'));
        $this->enter();
        // Wait for the page to reload
        $this->waitUntil(function () {
            return $this->driver->executeScript('return document.readyState') === 'complete';
        });
        // And for the loading screen to disappear
        $this->notSee('#overlay');
        $this->goto('albums');
        // and make sure the scanning is good.
        $this->seeText('Koel Testing Vol', '#albumsWrapper');
    }
}
