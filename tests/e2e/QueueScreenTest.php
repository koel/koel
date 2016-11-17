<?php

namespace E2E;

class QueueScreenTest extends TestCase
{
    public function test()
    {
        $this->loginAndWait();
        $this->goto('queue');
        static::assertContains('Current Queue', $this->el('#queueWrapper > h1 > span')->getText());

        // As the queue is currently empty, the "Shuffling all song" link should be there
        $this->click('#queueWrapper a.start');
        $this->waitUntil(function () {
            return count($this->els('#queueWrapper .song-item'));
        });

        // Clear the queue
        $this->click('#queueWrapper .buttons button.btn.btn-red');
        static::assertEmpty($this->els('#queueWrapper tr.song-item'));
    }

}
