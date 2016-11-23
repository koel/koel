<?php

namespace E2E;

/**
 * Class ZSettingsScreenTest
 * The name is an ugly trick to force this test to run last, due to it changing the whole suite's
 * data, causing other tests to fail otherwise.
 *
 * @package E2E
 */
class ZSettingsScreenTest extends TestCase
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
