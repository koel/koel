<?php

namespace E2E;

/**
 * Class ZSettingsScreenTest
 * The name is an ugly trick to force this test to run last, due to it changing the whole suite's
 * data, causing other tests to fail otherwise.
 */
class ZSettingsScreenTest extends TestCase
{
    public function testSettingsScreen()
    {
        $this->loginAndGoTo('settings')
            ->typeIn('#inputSettingsPath', dirname(__DIR__.'/../songs'))
            ->enter()
            // Wait for the page to reload
            ->waitUntil(function () {
                return $this->driver->executeScript('return document.readyState') === 'complete';
            })
            // And for the loading screen to disappear
            ->notSee('#overlay')
            ->goto('albums')
            // and make sure the scanning is good.
            ->seeText('Koel Testing Vol', '#albumsWrapper');
    }
}
