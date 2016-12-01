<?php

namespace E2E;

/**
 * Class PlaybackControlsTest.
 *
 * Tests the playback controls (the footer buttons).
 */
class PlaybackControlsTest extends TestCase
{
    public function testPlaybackControls()
    {
        $this->loginAndWait();

        // Show and hide the extra panel
        $this->click('#mainFooter .control.info');
        $this->notSee('#extra');
        $this->click('#mainFooter .control.info');
        $this->see('#extra');

        // Show and hide the Presets
        $this->click('#mainFooter .control.equalizer');
        $this->see('#equalizer');
        // clicking anywhere else should close the equalizer
        $this->click('#extra');
        $this->notSee('#equalizer');

        // Circle around the repeat state
        $this->click('#mainFooter .control.repeat');
        $this->see('#mainFooter .control.repeat.REPEAT_ALL');
        $this->click('#mainFooter .control.repeat');
        $this->see('#mainFooter .control.repeat.REPEAT_ONE');
        $this->click('#mainFooter .control.repeat');
        $this->see('#mainFooter .control.repeat.NO_REPEAT');

        // Mute/unmute
        $currentValue = $this->el('#volumeRange')->getAttribute('value');
        $this->click('#volume .fa-volume-up');
        $this->see('#mainFooter .fa-volume-off');
        static::assertEquals(0, $this->el('#volumeRange')->getAttribute('value'));
        $this->click('#volume .fa-volume-off');
        $this->see('#mainFooter .fa-volume-up');
        static::assertEquals($currentValue, $this->el('#volumeRange')->getAttribute('value'));
    }
}
